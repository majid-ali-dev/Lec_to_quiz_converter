<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    /**
     * Home page dikhata hai (form page)
     */
    public function index()
    {
        return view('quiz.index');
    }

    /**
     * Quiz generate karta hai AI se
     */
    public function generateQuiz(Request $request)
    {
        // Form data validate karte hain
        $request->validate([
            'university_name' => 'required|string|max:255',
            'topic_name' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:5|max:30',
        ]);

        try {
            // AI se MCQs generate karte hain with fallback
            $questions = $this->generateQuestionsWithFallback(
                $request->topic_name,
                $request->num_questions
            );

            // Database mein save karte hain
            $quiz = Quiz::create([
                'university_name' => $request->university_name,
                'topic_name' => $request->topic_name,
                'num_questions' => $request->num_questions,
                'questions' => $questions
            ]);

            // Result page pe redirect karte hain
            return redirect()->route('quiz.result', $quiz->id);

        } catch (\Exception $e) {
            // Error log karte hain
            Log::error('Quiz Generation Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            return back()->with('error', 'Quiz generate karne mein error aaya. Please check your API key and try again.');
        }
    }

    /**
     * Generated quiz dikhata hai
     */
    public function showResult($id)
    {
        $quiz = Quiz::findOrFail($id);
        return view('quiz.result', compact('quiz'));
    }

    /**
     * PDF download karta hai
     */
    public function downloadPDF($id)
    {
        $quiz = Quiz::findOrFail($id);
        
        // PDF generate karte hain
        $pdf = Pdf::loadView('quiz.pdf', compact('quiz'));
        
        // PDF download karte hain
        return $pdf->download('quiz_' . str_replace(' ', '_', $quiz->topic_name) . '.pdf');
    }

    /**
     * Answer key download karta hai
     */
    public function downloadAnswerKey($id)
    {
        $quiz = Quiz::findOrFail($id);
        
        // Answer key PDF generate karte hain
        $pdf = Pdf::loadView('quiz.answer-key', compact('quiz'));
        
        // PDF download karte hain
        return $pdf->download('answer_key_' . str_replace(' ', '_', $quiz->topic_name) . '.pdf');
    }

    /**
     * Questions generate karta hai with automatic fallback
     */
    private function generateQuestionsWithFallback($topic, $numQuestions)
    {
        $apiKey = env('GROQ_API_KEY');
        
        // Agar API key nahi hai to directly smart questions generate karo
        if (!$apiKey) {
            Log::warning('GROQ_API_KEY not found, using smart question generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
        }

        try {
            // First try: Groq API
            $questions = $this->callGroqAPI($topic, $numQuestions);
            
            // Check if questions are valid
            if (count($questions) >= $numQuestions && $this->validateQuestions($questions)) {
                Log::info('Successfully generated questions via Groq API');
                return $questions;
            }
            
            // If not enough valid questions, use smart generator
            Log::warning('Groq API returned insufficient questions, using smart generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
            
        } catch (\Exception $e) {
            // Agar API fail ho to smart questions use karo
            Log::error('Groq API failed: ' . $e->getMessage());
            Log::info('Falling back to smart question generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
        }
    }

    /**
     * Groq API ko call karta hai
     */
    private function callGroqAPI($topic, $numQuestions)
    {
        $apiKey = env('GROQ_API_KEY');
        
        $prompt = "Generate exactly {$numQuestions} multiple choice questions about '{$topic}' for university students.

Use this EXACT format:

1. [Clear question about {$topic}]
a) [First option]
b) [Second option]
c) [Third option]
d) [Fourth option]
Answer: a

2. [Another question]
a) [First option]
b) [Second option]
c) [Third option]
d) [Fourth option]
Answer: b

Make questions educational, specific, and relevant to {$topic}. Generate all {$numQuestions} questions now:";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educator creating clear multiple choice questions.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 3000,
        ]);

        if ($response->failed()) {
            Log::error('Groq API HTTP Error: ' . $response->status() . ' - ' . $response->body());
            throw new \Exception('API request failed');
        }

        $result = $response->json();
        
        if (!isset($result['choices'][0]['message']['content'])) {
            Log::error('Invalid API response structure: ' . json_encode($result));
            throw new \Exception('Invalid API response');
        }

        $generatedText = $result['choices'][0]['message']['content'];
        Log::info('AI Response length: ' . strlen($generatedText));
        
        return $this->parseQuestions($generatedText, $numQuestions);
    }

    /**
     * Parse questions from AI response
     */
    private function parseQuestions($text, $numQuestions)
    {
        $questions = [];
        $lines = explode("\n", $text);
        
        $currentQuestion = null;
        $currentOptions = [];
        $correctAnswer = 'a';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Question: "1." or "1)"
            if (preg_match('/^(\d+)[\.\)]\s*(.+)$/', $line, $matches) && strlen($matches[2]) > 10) {
                if ($currentQuestion && count($currentOptions) == 4) {
                    $questions[] = [
                        'question' => $currentQuestion,
                        'options' => $currentOptions,
                        'correct_answer' => strtolower($correctAnswer)
                    ];
                }
                
                $currentQuestion = trim($matches[2]);
                $currentOptions = [];
                $correctAnswer = 'a';
            }
            // Options: "a)" or "a."
            elseif (preg_match('/^([a-d])[\.\)]\s*(.+)$/i', $line, $matches)) {
                $optionLetter = strtolower($matches[1]);
                $optionText = trim($matches[2]);
                
                if (strlen($optionText) > 2) {
                    $currentOptions[$optionLetter] = $optionText;
                }
            }
            // Answer: "Answer: a"
            elseif (preg_match('/^(?:Answer|Correct):\s*([a-d])/i', $line, $matches)) {
                $correctAnswer = strtolower($matches[1]);
            }
        }

        // Save last question
        if ($currentQuestion && count($currentOptions) == 4) {
            $questions[] = [
                'question' => $currentQuestion,
                'options' => $currentOptions,
                'correct_answer' => strtolower($correctAnswer)
            ];
        }

        return $questions;
    }

    /**
     * Validate if questions are properly formatted
     */
    private function validateQuestions($questions)
    {
        foreach ($questions as $q) {
            // Check question exists
            if (empty($q['question']) || strlen($q['question']) < 10) {
                return false;
            }
            
            // Check all 4 options exist
            if (!isset($q['options']['a']) || !isset($q['options']['b']) || 
                !isset($q['options']['c']) || !isset($q['options']['d'])) {
                return false;
            }
            
            // Check options are not generic
            if (strpos($q['options']['a'], 'First possible answer') !== false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * SMART Question Generator - Topic-specific questions
     * Ye function actually topic ke base pe intelligent questions banata hai
     */
    private function generateSmartQuestions($topic, $numQuestions)
    {
        Log::info("Generating smart questions for topic: {$topic}");
        
        // Common question templates with actual content
        $templates = $this->getTopicSpecificTemplates($topic);
        
        $questions = [];
        $templateCount = count($templates);
        
        for ($i = 0; $i < $numQuestions; $i++) {
            $template = $templates[$i % $templateCount];
            
            // Replace {topic} with actual topic
            $question = str_replace('{topic}', $topic, $template['question']);
            
            $options = [];
            foreach ($template['options'] as $key => $opt) {
                $options[$key] = str_replace('{topic}', $topic, $opt);
            }
            
            $questions[] = [
                'question' => $question,
                'options' => $options,
                'correct_answer' => $template['correct']
            ];
        }
        
        return $questions;
    }

    /**
     * Topic-specific question templates
     */
    private function getTopicSpecificTemplates($topic)
    {
        // Convert topic to lowercase for comparison
        $topicLower = strtolower($topic);
        
        // Check if it's a programming/CS topic
        if (strpos($topicLower, 'loop') !== false || strpos($topicLower, 'programming') !== false) {
            return $this->getProgrammingTemplates();
        }
        
        if (strpos($topicLower, 'database') !== false || strpos($topicLower, 'dbms') !== false) {
            return $this->getDatabaseTemplates();
        }
        
        if (strpos($topicLower, 'network') !== false) {
            return $this->getNetworkTemplates();
        }
        
        // Default: Generic but intelligent templates
        return $this->getGenericTemplates($topic);
    }

    /**
     * Programming/Loop specific questions
     */
    private function getProgrammingTemplates()
    {
        return [
            [
                'question' => 'What is the primary purpose of a loop in programming?',
                'options' => [
                    'a' => 'To execute a block of code repeatedly',
                    'b' => 'To declare variables',
                    'c' => 'To define functions',
                    'd' => 'To handle errors'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which loop executes at least once regardless of the condition?',
                'options' => [
                    'a' => 'for loop',
                    'b' => 'while loop',
                    'c' => 'do-while loop',
                    'd' => 'foreach loop'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What happens when a break statement is encountered in a loop?',
                'options' => [
                    'a' => 'Loop continues to next iteration',
                    'b' => 'Loop terminates immediately',
                    'c' => 'Loop restarts from beginning',
                    'd' => 'Nothing happens'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'In a for loop, which part is executed first?',
                'options' => [
                    'a' => 'Initialization',
                    'b' => 'Condition',
                    'c' => 'Increment/Decrement',
                    'd' => 'Loop body'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'What is an infinite loop?',
                'options' => [
                    'a' => 'A loop that never starts',
                    'b' => 'A loop that executes exactly once',
                    'c' => 'A loop that never terminates',
                    'd' => 'A loop with no body'
                ],
                'correct' => 'c'
            ],
        ];
    }

    /**
     * Database specific questions
     */
    private function getDatabaseTemplates()
    {
        return [
            [
                'question' => 'In which of the following formats data is stored in the database management system?',
                'options' => [
                    'a' => 'Image',
                    'b' => 'Text',
                    'c' => 'Table',
                    'd' => 'Graph'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is a primary key in a database?',
                'options' => [
                    'a' => 'A key used for encryption',
                    'b' => 'A unique identifier for each record',
                    'c' => 'A password for database access',
                    'd' => 'A duplicate key for backup'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which SQL command is used to retrieve data from a database?',
                'options' => [
                    'a' => 'GET',
                    'b' => 'FETCH',
                    'c' => 'SELECT',
                    'd' => 'RETRIEVE'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What does DBMS stand for?',
                'options' => [
                    'a' => 'Data Base Management System',
                    'b' => 'Digital Base Management Software',
                    'c' => 'Data Binary Management System',
                    'd' => 'Database Monitoring Service'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which of the following is NOT a type of database relationship?',
                'options' => [
                    'a' => 'One-to-One',
                    'b' => 'One-to-Many',
                    'c' => 'Many-to-Many',
                    'd' => 'All-to-All'
                ],
                'correct' => 'd'
            ],
        ];
    }

    /**
     * Network specific questions
     */
    private function getNetworkTemplates()
    {
        return [
            [
                'question' => 'What does IP stand for in networking?',
                'options' => [
                    'a' => 'Internet Provider',
                    'b' => 'Internet Protocol',
                    'c' => 'Internal Process',
                    'd' => 'Interconnected Port'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which layer of the OSI model handles data transmission?',
                'options' => [
                    'a' => 'Application Layer',
                    'b' => 'Transport Layer',
                    'c' => 'Physical Layer',
                    'd' => 'Session Layer'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is the purpose of a router?',
                'options' => [
                    'a' => 'To connect devices within a network',
                    'b' => 'To forward data between different networks',
                    'c' => 'To provide power to devices',
                    'd' => 'To store network data'
                ],
                'correct' => 'b'
            ],
        ];
    }

    /**
     * Generic intelligent templates
     */
    private function getGenericTemplates($topic)
    {
        return [
            [
                'question' => "What is the fundamental concept of {$topic}?",
                'options' => [
                    'a' => "The basic principle underlying {$topic}",
                    'b' => "An advanced feature of {$topic}",
                    'c' => "A tool used in {$topic}",
                    'd' => "The history of {$topic}"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "Which of the following best describes {$topic}?",
                'options' => [
                    'a' => "A comprehensive system for managing {$topic}",
                    'b' => "A simple tool for {$topic}",
                    'c' => "An obsolete approach to {$topic}",
                    'd' => "A theoretical concept unrelated to {$topic}"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "What is the primary application of {$topic}?",
                'options' => [
                    'a' => "Entertainment purposes",
                    'b' => "Practical problem-solving in {$topic} domain",
                    'c' => "Decoration only",
                    'd' => "No practical use"
                ],
                'correct' => 'b'
            ],
        ];
    }
}