<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class QuizController extends Controller
{
    /**
     * Display the home page (form page)
     */
    public function index()
    {
        return view('quiz.index');
    }

    /**
     * Generate quiz using AI
     */
    public function generateQuiz(Request $request)
    {
        // Validate form data
        $request->validate([
            'university_name' => 'required|string|max:255',
            'topic_name' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:5|max:30',
        ]);

        try {
            // Generate MCQs using AI with fallback
            $questions = $this->generateQuestionsWithFallback(
                $request->topic_name,
                $request->num_questions
            );

            // Save to database
            $quiz = Quiz::create([
                'university_name' => $request->university_name,
                'topic_name' => $request->topic_name,
                'num_questions' => $request->num_questions,
                'questions' => $questions
            ]);

            // Redirect to result page
            return redirect()->route('quiz.result', $quiz->id);
        } catch (\Exception $e) {
            // Log error details
            Log::error('Quiz Generation Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error generating quiz. Please check your API key and try again.');
        }
    }

    /**
     * Display generated quiz
     */
    public function showResult($id)
    {
        $quiz = Quiz::findOrFail($id);
        return view('quiz.result', compact('quiz'));
    }

    /**
     * Download quiz as PDF
     */
    public function downloadPDF($id)
    {
        $quiz = Quiz::findOrFail($id);

        // Generate PDF
        $pdf = Pdf::loadView('quiz.pdf', compact('quiz'));

        // Download PDF
        return $pdf->download('quiz_' . str_replace(' ', '_', $quiz->topic_name) . '.pdf');
    }

    /**
     * Download quiz as DOCX
     */
    public function downloadDOCX($id)
    {
        $quiz = Quiz::findOrFail($id);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $titleStyle = ['bold' => true, 'size' => 16];
        $headingStyle = ['bold' => true, 'size' => 12];

        $section->addText($quiz->university_name . ' - Question Paper', $titleStyle);
        $section->addText('Topic: ' . $quiz->topic_name);
        $section->addTextBreak(1);

        foreach ($quiz->questions as $index => $q) {
            $number = $index + 1;
            $section->addText($number . '. ' . ($q['question'] ?? ''), $headingStyle);
            if (isset($q['options']) && is_array($q['options'])) {
                foreach (['a', 'b', 'c', 'd'] as $letter) {
                    if (isset($q['options'][$letter])) {
                        $section->addText("  {$letter}) " . $q['options'][$letter]);
                    }
                }
            }
            $section->addTextBreak(1);
        }

        $fileName = 'quiz_' . str_replace(' ', '_', $quiz->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Download answer key as PDF
     */
    public function downloadAnswerKeyPDF($id)
    {
        $quiz = Quiz::findOrFail($id);

        // Generate answer key PDF
        $pdf = Pdf::loadView('quiz.answer-key', compact('quiz'));

        // Download PDF
        return $pdf->download('answer_key_' . str_replace(' ', '_', $quiz->topic_name) . '.pdf');
    }

    /**
     * Download answer key as DOCX
     */
    public function downloadAnswerKeyDOCX($id)
    {
        $quiz = Quiz::findOrFail($id);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $titleStyle = ['bold' => true, 'size' => 16];
        $section->addText('CONFIDENTIAL - Answer Key', $titleStyle);
        $section->addText('Topic: ' . $quiz->topic_name);
        $section->addTextBreak(1);

        foreach ($quiz->questions as $index => $q) {
            $number = $index + 1;
            $answer = isset($q['correct_answer']) ? strtoupper($q['correct_answer']) : '-';
            $section->addText("Q{$number}: {$answer}");
        }

        $fileName = 'answer_key_' . str_replace(' ', '_', $quiz->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Generate questions with automatic fallback
     */
    private function generateQuestionsWithFallback($topic, $numQuestions)
    {
        $apiKey = env('GROQ_API_KEY');

        // If no API key, use smart question generator
        if (!$apiKey) {
            Log::warning('GROQ_API_KEY not found, using smart question generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
        }

        try {
            // Try Groq API first
            $questions = $this->callGroqAPI($topic, $numQuestions);

            // Validate and deduplicate questions
            $questions = $this->deduplicateQuestions($questions);

            // Check if we have enough valid questions
            if (count($questions) >= $numQuestions && $this->validateQuestions($questions)) {
                Log::info('Successfully generated questions via Groq API');
                return array_slice($questions, 0, $numQuestions);
            }

            // If not enough questions, use smart generator
            Log::warning('Groq API returned insufficient questions, using smart generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
        } catch (\Exception $e) {
            // On API failure, use smart questions
            Log::error('Groq API failed: ' . $e->getMessage());
            Log::info('Falling back to smart question generator');
            return $this->generateSmartQuestions($topic, $numQuestions);
        }
    }

    /**
     * Call Groq API for question generation
     */
    private function callGroqAPI($topic, $numQuestions)
    {
        $apiKey = env('GROQ_API_KEY');

        $prompt = "You are an expert university educator. Create exactly {$numQuestions} UNIQUE high-quality MCQs specifically about '{$topic}'.

CRITICAL RULES:
1. Each question MUST be directly related to {$topic} - no generic questions
2. All questions MUST be completely unique - no duplicates or similar variations
3. Each question must have exactly 4 options labeled a), b), c), d)
4. Exactly ONE correct answer per question
5. Options must be plausible and test real knowledge
6. Use professional academic language
7. Questions should test understanding, not memorization
8. NO questions like 'What is {$topic}?' or 'Define {$topic}'
9. Make questions specific with real examples from {$topic} domain

OUTPUT FORMAT (exactly like this for each question):
1. [Specific question about {$topic}]
a) [Option A]
b) [Option B]
c) [Option C]
d) [Option D]
Answer: [correct letter]

Create diverse questions covering different aspects of {$topic}. Each question must be substantially different from others.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educator. Create unique, topic-specific multiple choice questions. Never repeat questions.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.8, // Increased for more variety
            'max_tokens' => 4000,
            'top_p' => 0.95,
            'frequency_penalty' => 1.0, // Penalize repetition
            'presence_penalty' => 0.6
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
     * Parse questions from AI response with improved deduplication
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

            // Match question pattern: "1." or "1)" or "Q1:"
            if (preg_match('/^(?:Q?\d+[\.KATEX_INLINE_CLOSE:]?\s*)?(.+)$/i', $line, $matches)) {
                $potentialQuestion = trim($matches[1]);

                // Check if this is actually a question (contains ? or is long enough)
                if ((strpos($potentialQuestion, '?') !== false || strlen($potentialQuestion) > 30)
                    && !preg_match('/^[a-d][\.KATEX_INLINE_CLOSE]/i', $potentialQuestion)
                ) {

                    // Save previous question if complete
                    if ($currentQuestion && count($currentOptions) == 4) {
                        $questions[] = [
                            'question' => $currentQuestion,
                            'options' => $currentOptions,
                            'correct_answer' => strtolower($correctAnswer)
                        ];
                    }

                    $currentQuestion = $potentialQuestion;
                    $currentOptions = [];
                    $correctAnswer = 'a';
                }
            }

            // Match options: "a)" or "a."
            if (preg_match('/^([a-d])[\.KATEX_INLINE_CLOSE]\s*(.+)$/i', $line, $matches)) {
                $optionLetter = strtolower($matches[1]);
                $optionText = trim($matches[2]);

                if (strlen($optionText) > 1) {
                    $currentOptions[$optionLetter] = $optionText;
                }
            }

            // Match answer: "Answer: a" or "Correct: a"
            if (preg_match('/^(?:Answer|Correct|Ans)[\s:]+([a-d])/i', $line, $matches)) {
                $correctAnswer = strtolower($matches[1]);
            }
        }

        // Save last question if complete
        if ($currentQuestion && count($currentOptions) == 4) {
            $questions[] = [
                'question' => $currentQuestion,
                'options' => $currentOptions,
                'correct_answer' => strtolower($correctAnswer)
            ];
        }

        // Strict deduplication
        return $this->deduplicateQuestions($questions);
    }

    /**
     * Remove duplicate questions with improved logic
     */
    private function deduplicateQuestions($questions)
    {
        $uniqueQuestions = [];
        $seenQuestions = [];
        $seenPatterns = [];

        foreach ($questions as $q) {
            // Normalize question for comparison
            $normalizedQ = strtolower(preg_replace('/[^a-z0-9]+/', '', $q['question']));

            // Extract key words for pattern matching
            $words = str_word_count(strtolower($q['question']), 1);
            $keyWords = array_slice($words, 0, 5); // First 5 words
            $pattern = implode('', $keyWords);

            // Check for exact duplicates and similar patterns
            if (!isset($seenQuestions[$normalizedQ]) && !isset($seenPatterns[$pattern])) {
                $seenQuestions[$normalizedQ] = true;
                $seenPatterns[$pattern] = true;
                $uniqueQuestions[] = $q;
            }
        }

        return $uniqueQuestions;
    }

    /**
     * Validate if questions are properly formatted and topic-relevant
     */
    private function validateQuestions($questions)
    {
        if (!is_array($questions) || empty($questions)) {
            return false;
        }

        foreach ($questions as $q) {
            // Check question exists and is substantial
            if (empty($q['question']) || strlen($q['question']) < 10) {
                return false;
            }

            // Check all 4 options exist
            if (
                !isset($q['options']['a']) || !isset($q['options']['b']) ||
                !isset($q['options']['c']) || !isset($q['options']['d'])
            ) {
                return false;
            }

            // Check options are not generic or placeholder text
            foreach (['a', 'b', 'c', 'd'] as $letter) {
                $option = strtolower($q['options'][$letter]);
                if (
                    strlen(trim($q['options'][$letter])) < 2 ||
                    strpos($option, 'option') !== false ||
                    strpos($option, 'answer') !== false ||
                    strpos($option, 'first') !== false ||
                    strpos($option, 'second') !== false
                ) {
                    return false;
                }
            }

            // Correct answer must be a-d
            if (!isset($q['correct_answer']) || !in_array(strtolower($q['correct_answer']), ['a', 'b', 'c', 'd'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate smart topic-specific questions with better variety
     */
    private function generateSmartQuestions($topic, $numQuestions)
    {
        Log::info("Generating smart questions for topic: {$topic}");

        // Get all possible templates for the topic
        $templates = $this->getTopicSpecificTemplates($topic);

        // Shuffle templates for variety
        shuffle($templates);

        // If we need more questions than templates, generate variations
        if ($numQuestions > count($templates)) {
            $templates = $this->expandTemplates($templates, $topic, $numQuestions);
        }

        $questions = [];
        $usedQuestions = [];

        for ($i = 0; $i < $numQuestions; $i++) {
            if (isset($templates[$i])) {
                $template = $templates[$i];

                // Replace {topic} placeholder
                $question = str_replace('{topic}', $topic, $template['question']);

                // Skip if this exact question was already used
                $questionKey = strtolower(preg_replace('/[^a-z0-9]+/', '', $question));
                if (isset($usedQuestions[$questionKey])) {
                    continue;
                }
                $usedQuestions[$questionKey] = true;

                $options = [];
                foreach ($template['options'] as $key => $opt) {
                    $options[$key] = str_replace('{topic}', $topic, $opt);
                }

                // Randomize correct answer position occasionally
                if (rand(0, 100) > 70) {
                    $options = $this->shuffleOptions($options, $template['correct']);
                    $template['correct'] = array_search($options[$template['correct']], $options);
                }

                $questions[] = [
                    'question' => $question,
                    'options' => $options,
                    'correct_answer' => $template['correct']
                ];
            }
        }

        return $questions;
    }

    /**
     * Shuffle options while maintaining correct answer tracking
     */
    private function shuffleOptions($options, &$correctAnswer)
    {
        $correctText = $options[$correctAnswer];
        $optionValues = array_values($options);
        shuffle($optionValues);

        $newOptions = [];
        $letters = ['a', 'b', 'c', 'd'];

        foreach ($letters as $index => $letter) {
            $newOptions[$letter] = $optionValues[$index];
            if ($optionValues[$index] === $correctText) {
                $correctAnswer = $letter;
            }
        }

        return $newOptions;
    }

    /**
     * Expand templates with variations
     */
    private function expandTemplates($templates, $topic, $needed)
    {
        $expanded = $templates;
        $variations = [
            'Which of the following best describes',
            'What is the primary characteristic of',
            'In the context of {topic}, what is',
            'Which statement about {topic} is correct',
            'What distinguishes {topic} from other approaches',
            'The main advantage of {topic} is',
            'When working with {topic}, which is true',
            'A key feature of {topic} includes',
            'In {topic}, the most important aspect is',
            'Which principle applies to {topic}'
        ];

        while (count($expanded) < $needed) {
            foreach ($templates as $template) {
                if (count($expanded) >= $needed) break;

                $variation = $variations[array_rand($variations)];
                $newQuestion = $variation . ' ' . str_replace(['What is', 'Which'], '', $template['question']);

                $expanded[] = [
                    'question' => $newQuestion,
                    'options' => $template['options'],
                    'correct' => $template['correct']
                ];
            }
        }

        return $expanded;
    }

    /**
     * Get topic-specific question templates
     */
    private function getTopicSpecificTemplates($topic)
    {
        $topicLower = strtolower($topic);

        // Programming topics
        if (
            strpos($topicLower, 'loop') !== false ||
            strpos($topicLower, 'programming') !== false ||
            strpos($topicLower, 'code') !== false ||
            strpos($topicLower, 'algorithm') !== false
        ) {
            return $this->getProgrammingTemplates();
        }

        // Database topics
        if (
            strpos($topicLower, 'database') !== false ||
            strpos($topicLower, 'dbms') !== false ||
            strpos($topicLower, 'sql') !== false ||
            strpos($topicLower, 'mysql') !== false
        ) {
            return $this->getDatabaseTemplates();
        }

        // Network topics
        if (
            strpos($topicLower, 'network') !== false ||
            strpos($topicLower, 'tcp') !== false ||
            strpos($topicLower, 'ip') !== false ||
            strpos($topicLower, 'protocol') !== false
        ) {
            return $this->getNetworkTemplates();
        }

        // Web development topics
        if (
            strpos($topicLower, 'web') !== false ||
            strpos($topicLower, 'html') !== false ||
            strpos($topicLower, 'css') !== false ||
            strpos($topicLower, 'javascript') !== false
        ) {
            return $this->getWebTemplates();
        }

        // Data structure topics
        if (
            strpos($topicLower, 'data structure') !== false ||
            strpos($topicLower, 'array') !== false ||
            strpos($topicLower, 'stack') !== false ||
            strpos($topicLower, 'queue') !== false
        ) {
            return $this->getDataStructureTemplates();
        }

        // Default: Generate intelligent generic templates
        return $this->getGenericTemplates($topic);
    }

    /**
     * Programming specific question templates
     */
    private function getProgrammingTemplates()
    {
        return [
            [
                'question' => 'What is the primary purpose of a loop in programming?',
                'options' => [
                    'a' => 'To execute a block of code repeatedly based on a condition',
                    'b' => 'To declare variables in the program',
                    'c' => 'To define functions and methods',
                    'd' => 'To handle errors and exceptions'
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
                'question' => 'What happens when a break statement is encountered inside a loop?',
                'options' => [
                    'a' => 'Loop continues to the next iteration',
                    'b' => 'Loop terminates immediately',
                    'c' => 'Loop restarts from the beginning',
                    'd' => 'Nothing happens to the loop'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'In a for loop, which component is executed first?',
                'options' => [
                    'a' => 'Initialization statement',
                    'b' => 'Condition check',
                    'c' => 'Increment/Decrement operation',
                    'd' => 'Loop body statements'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'What is an infinite loop?',
                'options' => [
                    'a' => 'A loop that never starts execution',
                    'b' => 'A loop that executes exactly once',
                    'c' => 'A loop that never terminates naturally',
                    'd' => 'A loop with no body statements'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'Which keyword is used to skip the current iteration in a loop?',
                'options' => [
                    'a' => 'break',
                    'b' => 'continue',
                    'c' => 'return',
                    'd' => 'exit'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'What is the time complexity of a single for loop iterating n times?',
                'options' => [
                    'a' => 'O(1)',
                    'b' => 'O(n)',
                    'c' => 'O(n²)',
                    'd' => 'O(log n)'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Nested loops with n iterations each have what time complexity?',
                'options' => [
                    'a' => 'O(n)',
                    'b' => 'O(2n)',
                    'c' => 'O(n²)',
                    'd' => 'O(n log n)'
                ],
                'correct' => 'c'
            ]
        ];
    }

    /**
     * Database specific question templates
     */
    private function getDatabaseTemplates()
    {
        return [
            [
                'question' => 'In which format is data primarily stored in a relational database?',
                'options' => [
                    'a' => 'Image files',
                    'b' => 'Text documents',
                    'c' => 'Tables with rows and columns',
                    'd' => 'Graph structures'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is a primary key in a database table?',
                'options' => [
                    'a' => 'A key used for encryption',
                    'b' => 'A unique identifier for each record',
                    'c' => 'A password for database access',
                    'd' => 'A duplicate key for backup'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which SQL command retrieves data from a database?',
                'options' => [
                    'a' => 'GET',
                    'b' => 'FETCH',
                    'c' => 'SELECT',
                    'd' => 'RETRIEVE'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What does ACID stand for in database transactions?',
                'options' => [
                    'a' => 'Atomicity, Consistency, Isolation, Durability',
                    'b' => 'Addition, Creation, Insertion, Deletion',
                    'c' => 'Access, Control, Index, Data',
                    'd' => 'Automatic, Concurrent, Independent, Direct'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which SQL command is used to modify existing data?',
                'options' => [
                    'a' => 'MODIFY',
                    'b' => 'UPDATE',
                    'c' => 'CHANGE',
                    'd' => 'ALTER'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'What is normalization in database design?',
                'options' => [
                    'a' => 'Making all data uppercase',
                    'b' => 'Organizing data to reduce redundancy',
                    'c' => 'Compressing database files',
                    'd' => 'Converting to a standard format'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which JOIN returns all records when there is a match in either table?',
                'options' => [
                    'a' => 'INNER JOIN',
                    'b' => 'LEFT JOIN',
                    'c' => 'RIGHT JOIN',
                    'd' => 'FULL OUTER JOIN'
                ],
                'correct' => 'd'
            ],
            [
                'question' => 'What is a foreign key in a database?',
                'options' => [
                    'a' => 'A key from another country',
                    'b' => 'A field that links to the primary key of another table',
                    'c' => 'An encrypted key',
                    'd' => 'A backup key'
                ],
                'correct' => 'b'
            ]
        ];
    }

    /**
     * Network specific question templates
     */
    private function getNetworkTemplates()
    {
        return [
            [
                'question' => 'What does TCP stand for in networking?',
                'options' => [
                    'a' => 'Transfer Control Protocol',
                    'b' => 'Transmission Control Protocol',
                    'c' => 'Technical Communication Protocol',
                    'd' => 'Transport Communication Process'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which layer of the OSI model handles routing?',
                'options' => [
                    'a' => 'Physical Layer',
                    'b' => 'Data Link Layer',
                    'c' => 'Network Layer',
                    'd' => 'Transport Layer'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is the purpose of a subnet mask?',
                'options' => [
                    'a' => 'To hide IP addresses',
                    'b' => 'To determine network and host portions of an IP',
                    'c' => 'To encrypt network traffic',
                    'd' => 'To compress data packets'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which protocol is connectionless?',
                'options' => [
                    'a' => 'TCP',
                    'b' => 'FTP',
                    'c' => 'UDP',
                    'd' => 'SSH'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is the default port for HTTP?',
                'options' => [
                    'a' => '21',
                    'b' => '80',
                    'c' => '443',
                    'd' => '8080'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which device operates at Layer 2 of the OSI model?',
                'options' => [
                    'a' => 'Router',
                    'b' => 'Switch',
                    'c' => 'Hub',
                    'd' => 'Gateway'
                ],
                'correct' => 'b'
            ]
        ];
    }

    /**
     * Web development specific templates
     */
    private function getWebTemplates()
    {
        return [
            [
                'question' => 'What does HTML stand for?',
                'options' => [
                    'a' => 'Hyper Text Markup Language',
                    'b' => 'High Tech Modern Language',
                    'c' => 'Hyper Transfer Markup Language',
                    'd' => 'Home Tool Markup Language'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which CSS property controls text color?',
                'options' => [
                    'a' => 'text-color',
                    'b' => 'color',
                    'c' => 'font-color',
                    'd' => 'text-style'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'What is the correct way to link a CSS file in HTML?',
                'options' => [
                    'a' => '<link rel="stylesheet" href="style.css">',
                    'b' => '<css src="style.css">',
                    'c' => '<style src="style.css">',
                    'd' => '<stylesheet>style.css</stylesheet>'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which JavaScript method adds an element to the end of an array?',
                'options' => [
                    'a' => 'append()',
                    'b' => 'push()',
                    'c' => 'add()',
                    'd' => 'insert()'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'What is the purpose of responsive web design?',
                'options' => [
                    'a' => 'To make websites load faster',
                    'b' => 'To adapt layout to different screen sizes',
                    'c' => 'To improve SEO rankings',
                    'd' => 'To reduce server load'
                ],
                'correct' => 'b'
            ]
        ];
    }

    /**
     * Data structure specific templates
     */
    private function getDataStructureTemplates()
    {
        return [
            [
                'question' => 'What is the time complexity of accessing an array element by index?',
                'options' => [
                    'a' => 'O(1)',
                    'b' => 'O(n)',
                    'c' => 'O(log n)',
                    'd' => 'O(n²)'
                ],
                'correct' => 'a'
            ],
            [
                'question' => 'Which data structure follows LIFO principle?',
                'options' => [
                    'a' => 'Queue',
                    'b' => 'Stack',
                    'c' => 'Array',
                    'd' => 'Linked List'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'What is the main advantage of a linked list over an array?',
                'options' => [
                    'a' => 'Faster access time',
                    'b' => 'Dynamic size allocation',
                    'c' => 'Less memory usage',
                    'd' => 'Better cache locality'
                ],
                'correct' => 'b'
            ],
            [
                'question' => 'Which operation is most efficient in a hash table?',
                'options' => [
                    'a' => 'Sorting',
                    'b' => 'Sequential access',
                    'c' => 'Key-based lookup',
                    'd' => 'Range queries'
                ],
                'correct' => 'c'
            ],
            [
                'question' => 'What is the worst-case time complexity of binary search?',
                'options' => [
                    'a' => 'O(1)',
                    'b' => 'O(n)',
                    'c' => 'O(log n)',
                    'd' => 'O(n log n)'
                ],
                'correct' => 'c'
            ]
        ];
    }

    /**
     * Generic intelligent templates for any topic
     */
    private function getGenericTemplates($topic)
    {
        return [
            [
                'question' => "What is the fundamental principle underlying {$topic}?",
                'options' => [
                    'a' => "The core concept that defines how {$topic} operates",
                    'b' => "A secondary feature of {$topic}",
                    'c' => "An outdated approach to {$topic}",
                    'd' => "An unrelated concept to {$topic}"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "Which characteristic best defines {$topic} in modern practice?",
                'options' => [
                    'a' => "Its comprehensive and systematic approach",
                    'b' => "Its limited scope and application",
                    'c' => "Its obsolete methodology",
                    'd' => "Its theoretical nature only"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "What is the primary benefit of implementing {$topic}?",
                'options' => [
                    'a' => "Reduced complexity only",
                    'b' => "Improved efficiency and effectiveness",
                    'c' => "Cosmetic improvements only",
                    'd' => "No significant benefits"
                ],
                'correct' => 'b'
            ],
            [
                'question' => "In the context of {$topic}, what is most critical for success?",
                'options' => [
                    'a' => "Understanding the underlying principles",
                    'b' => "Memorizing definitions only",
                    'c' => "Avoiding the topic entirely",
                    'd' => "Using outdated methods"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "How does {$topic} differ from traditional approaches?",
                'options' => [
                    'a' => "It uses modern, optimized methods",
                    'b' => "It is exactly the same",
                    'c' => "It is less efficient",
                    'd' => "It has no practical application"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "What challenge is commonly faced when working with {$topic}?",
                'options' => [
                    'a' => "Initial learning curve and complexity",
                    'b' => "Complete impossibility of implementation",
                    'c' => "Lack of any documentation",
                    'd' => "No challenges exist"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "Which industry or field benefits most from {$topic}?",
                'options' => [
                    'a' => "Multiple industries with varied applications",
                    'b' => "No industry at all",
                    'c' => "Only theoretical research",
                    'd' => "Exclusively academic settings"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "What prerequisite knowledge helps in understanding {$topic}?",
                'options' => [
                    'a' => "Fundamental concepts in the related field",
                    'b' => "No prerequisites needed",
                    'c' => "Advanced quantum physics only",
                    'd' => "Unrelated subjects"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "How has {$topic} evolved over time?",
                'options' => [
                    'a' => "Continuous improvement and refinement",
                    'b' => "No change whatsoever",
                    'c' => "Complete abandonment",
                    'd' => "Regression to older methods"
                ],
                'correct' => 'a'
            ],
            [
                'question' => "What is a common misconception about {$topic}?",
                'options' => [
                    'a' => "That it is more complex than it actually is",
                    'b' => "That it always works perfectly",
                    'c' => "That it has no real-world application",
                    'd' => "That everyone understands it completely"
                ],
                'correct' => 'a'
            ]
        ];
    }
}
