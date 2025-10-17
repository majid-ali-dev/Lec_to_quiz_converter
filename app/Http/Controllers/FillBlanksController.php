<?php

namespace App\Http\Controllers;

use App\Models\FillBlanks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class FillBlanksController extends Controller
{
    // Show form page
    public function index()
    {
        return view('fill-blanks.index');
    }

    // Generate fill-in-the-blank questions
    public function generate(Request $request)
    {
        $request->validate([
            'university_name' => 'required|string|max:255',
            'topic_name' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:5|max:30',
        ]);

        try {
            // Generate questions with AI
            $items = $this->generateWithGroq($request->topic_name, $request->num_questions);

            // Save to database
            $model = FillBlanks::create([
                'university_name' => $request->university_name,
                'topic_name' => $request->topic_name,
                'num_questions' => $request->num_questions,
                'questions' => $items,
            ]);

            return redirect()->route('fillblanks.result', $model->id);
        } catch (\Exception $e) {
            Log::error('FillBlanks Generation Error: ' . $e->getMessage());
            return back()->with('error', 'Generation failed. Please try again.');
        }
    }

    // Display generated questions
    public function showResult($id)
    {
        $data = FillBlanks::findOrFail($id);
        return view('fill-blanks.result', compact('data'));
    }

    // Download as PDF
    public function downloadPDF($id)
    {
        $data = FillBlanks::findOrFail($id);
        $pdf = Pdf::loadView('fill-blanks.pdf', compact('data'));
        return $pdf->download('fill_blanks_' . str_replace(' ', '_', $data->topic_name) . '.pdf');
    }

    // Download as DOCX
    public function downloadDOCX($id)
    {
        $data = FillBlanks::findOrFail($id);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Header
        $section->addText($data->university_name . ' - Fill in the Blanks', ['bold' => true, 'size' => 16]);
        $section->addText('Topic: ' . $data->topic_name, ['size' => 12]);
        $section->addTextBreak(1);

        // Questions
        foreach ($data->questions as $index => $q) {
            $section->addText(($index + 1) . '. ' . $q['sentence'], ['bold' => true]);
            $section->addText('   Hint: ' . $q['hint'], ['italic' => true, 'color' => '666666']);
            $section->addTextBreak(1);
        }

        $fileName = 'fill_blanks_' . str_replace(' ', '_', $data->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    // Download answer key as PDF
    public function downloadAnswerKeyPDF($id)
    {
        $data = FillBlanks::findOrFail($id);
        $pdf = Pdf::loadView('fill-blanks.answer-key', compact('data'));
        return $pdf->download('fill_blanks_key_' . str_replace(' ', '_', $data->topic_name) . '.pdf');
    }

    // Download answer key as DOCX
    public function downloadAnswerKeyDOCX($id)
    {
        $data = FillBlanks::findOrFail($id);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText('CONFIDENTIAL - Answer Key', ['bold' => true, 'size' => 16, 'color' => 'FF0000']);
        $section->addText('Topic: ' . $data->topic_name, ['size' => 12]);
        $section->addTextBreak(1);

        foreach ($data->questions as $index => $q) {
            $section->addText('Q' . ($index + 1) . ': ' . $q['blank_word'], ['bold' => true]);
        }

        $fileName = 'fill_blanks_key_' . str_replace(' ', '_', $data->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    // Generate questions with Groq API - IMPROVED VERSION
    private function generateWithGroq(string $topic, int $num)
    {
        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            Log::warning('GROQ_API_KEY missing, using smart generator');
            return $this->smartGenerateWithTopic($topic, $num);
        }

        try {
            $maxRetries = 2;
            $retryCount = 0;

            while ($retryCount < $maxRetries) {
                $items = $this->callGroqWithEnhancedPrompt($topic, $num);

                if ($this->validateEnhancedItems($items, $num) && !$this->hasDuplicates($items)) {
                    Log::info('Successfully generated high-quality fill blanks via Groq API');
                    return $items;
                }

                $retryCount++;
                Log::warning("Attempt {$retryCount} failed, retrying...");
            }

            // If all retries fail, use smart generator
            Log::warning('All Groq attempts failed, using smart generator');
            return $this->smartGenerateWithTopic($topic, $num);
        } catch (\Exception $e) {
            Log::error('Groq API failed: ' . $e->getMessage());
            return $this->smartGenerateWithTopic($topic, $num);
        }
    }

    // Enhanced Groq API call with better prompt engineering
    private function callGroqWithEnhancedPrompt(string $topic, int $num)
    {
        $prompt = "CRITICAL INSTRUCTIONS FOR FILL-IN-THE-BLANK QUESTIONS ABOUT: '{$topic}'

STRICT REQUIREMENTS:
1. Generate EXACTLY {$num} UNIQUE, NON-REPETITIVE questions
2. EVERY question MUST be SPECIFICALLY about '{$topic}' - NO generic questions
3. Remove ONLY meaningful, important terms related to '{$topic}'
4. NEVER remove: 'the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for', 'of'
5. Each blank must test actual knowledge of '{$topic}'
6. Questions must make COMPLETE SENSE and be EDUCATIONAL
7. Ensure grammatical correctness and proper sentence structure
8. Focus on KEY CONCEPTS, TERMINOLOGY, and IMPORTANT FACTS about '{$topic}'

QUALITY CONTROL:
- No meaningless blanks
- No repeated concepts
- No trivial words
- All questions must be factually correct
- Each question should test different aspect of '{$topic}'

OUTPUT FORMAT: STRICT JSON array only
Each object must have: 
{
  \"sentence\": \"Complete sentence with _____ where blank appears\",
  \"blank_word\": \"the exact term removed\",
  \"hint\": \"helpful clue about the blank term\"
}

EXAMPLES for topic 'Artificial Intelligence':
{
  \"sentence\": \"_____ is the branch of computer science dealing with intelligent machines.\",
  \"blank_word\": \"Artificial Intelligence\", 
  \"hint\": \"Field of study for smart machines\"
}
{
  \"sentence\": \"The _____ algorithm is used for decision tree learning in machine learning.\",
  \"blank_word\": \"ID3\",
  \"hint\": \"Iterative Dichotomiser 3\"
}

NOW generate {$num} HIGH-QUALITY, UNIQUE fill-in-the-blank questions specifically about '{$topic}':";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            'Content-Type' => 'application/json',
        ])->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educator specializing in creating high-quality, unique fill-in-the-blank questions. You strictly follow all instructions and never repeat questions. You focus on meaningful, educational content that tests actual knowledge.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'top_p' => 0.9,
        ]);

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $result = $response->json();
        $content = $result['choices'][0]['message']['content'] ?? '';

        Log::info('Groq API Response:', ['content' => $content]);

        return $this->parseAndValidateItems($content, $num);
    }

    // Parse and validate items with enhanced checks
    private function parseAndValidateItems(string $content, int $expectedNum)
    {
        // Extract JSON from response
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            $json = $matches[0];
        } else {
            $json = $content;
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \Exception('Invalid JSON response from AI');
        }

        // Validate we got the expected number
        if (count($data) < $expectedNum) {
            throw new \Exception('Insufficient questions generated');
        }

        return array_slice($data, 0, $expectedNum);
    }

    // Enhanced validation with strict quality checks
    private function validateEnhancedItems(array $items, int $expected): bool
    {
        if (count($items) < $expected) {
            Log::warning('Insufficient items generated');
            return false;
        }

        // Words that should NEVER be blanks
        $forbiddenWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'but', 'with', 'by', 'as'];

        $usedWords = [];
        $usedSentences = [];

        foreach ($items as $index => $item) {
            // Check required fields
            if (empty($item['sentence']) || empty($item['blank_word']) || empty($item['hint'])) {
                Log::warning("Missing required fields in item {$index}");
                return false;
            }

            $sentence = trim($item['sentence']);
            $word = trim($item['blank_word']);
            $hint = trim($item['hint']);

            // Check for blank placeholder
            if (strpos($sentence, '_____') === false) {
                Log::warning("No blank placeholder in sentence: {$sentence}");
                return false;
            }

            // Check blank word is not forbidden
            $wordLower = strtolower($word);
            if (in_array($wordLower, $forbiddenWords)) {
                Log::warning("Forbidden word used as blank: {$word}");
                return false;
            }

            // Check blank word is meaningful (at least 3 characters)
            if (strlen($word) < 3) {
                Log::warning("Blank word too short: {$word}");
                return false;
            }

            // Check for duplicate words
            if (in_array($wordLower, $usedWords)) {
                Log::warning("Duplicate blank word: {$word}");
                return false;
            }
            $usedWords[] = $wordLower;

            // Check for duplicate sentences
            $sentenceKey = md5(strtolower($sentence));
            if (in_array($sentenceKey, $usedSentences)) {
                Log::warning("Duplicate sentence structure");
                return false;
            }
            $usedSentences[] = $sentenceKey;

            // Check grammatical sense
            if (!$this->checkGrammaticalSense($sentence, $word)) {
                Log::warning("Sentence doesn't make grammatical sense");
                return false;
            }
        }

        return true;
    }

    // Check if sentence makes grammatical sense
    private function checkGrammaticalSense(string $sentence, string $word): bool
    {
        // Basic checks for grammatical structure
        $tempSentence = str_replace('_____', $word, $sentence);

        // Should not have double spaces
        if (strpos($tempSentence, '  ') !== false) {
            return false;
        }

        // Should end with proper punctuation
        if (!preg_match('/[.!?]$/', trim($tempSentence))) {
            return false;
        }

        // Should not have obvious grammatical errors
        $errors = [' a a ', ' an a ', ' the the ', ' is are ', ' was were '];
        foreach ($errors as $error) {
            if (strpos(strtolower($tempSentence), $error) !== false) {
                return false;
            }
        }

        return true;
    }

    // Check for duplicate questions
    private function hasDuplicates(array $items): bool
    {
        $sentences = [];
        $words = [];

        foreach ($items as $item) {
            $sentence = strtolower(trim($item['sentence'] ?? ''));
            $word = strtolower(trim($item['blank_word'] ?? ''));

            if (in_array($sentence, $sentences) || in_array($word, $words)) {
                return true;
            }

            $sentences[] = $sentence;
            $words[] = $word;
        }

        return false;
    }

    // Enhanced smart generator with topic analysis - FIXED VERSION
    private function smartGenerateWithTopic(string $topic, int $num): array
    {
        $topicLower = strtolower(trim($topic));

        // Analyze topic and get appropriate templates - FIX: Always return array
        $templates = $this->analyzeTopicAndGetTemplates($topicLower, $num);

        // Ensure we always have an array
        if (!is_array($templates) || empty($templates)) {
            Log::warning('No templates found, using dynamic generation');
            $templates = $this->createDynamicTopicBlanks($topic, $num);
        }

        // Ensure uniqueness
        $uniqueTemplates = $this->ensureUniqueness($templates, $num);

        return array_slice($uniqueTemplates, 0, $num);
    }

    // Analyze topic and get relevant templates - FIXED: Always return array
    private function analyzeTopicAndGetTemplates(string $topic, int $num): array
    {
        // Programming topics
        if (strpos($topic, 'loop') !== false) {
            return $this->getLoopProgrammingBlanks();
        } elseif (strpos($topic, 'python') !== false) {
            return $this->getPythonBlanks();
        } elseif (strpos($topic, 'java') !== false) {
            return $this->getJavaBlanks();
        } elseif (strpos($topic, 'array') !== false) {
            return $this->getArrayBlanks();
        } elseif (strpos($topic, 'function') !== false) {
            return $this->getFunctionBlanks();
        } elseif (strpos($topic, 'object') !== false || strpos($topic, 'oop') !== false) {
            return $this->getOOPBlanks();
        } elseif (strpos($topic, 'programming') !== false || strpos($topic, 'code') !== false) {
            return $this->getProgrammingBlanks();
        }
        // Database topics
        elseif (strpos($topic, 'database') !== false || strpos($topic, 'sql') !== false) {
            return $this->getDatabaseBlanks();
        }
        // Network topics
        elseif (strpos($topic, 'network') !== false || strpos($topic, 'internet') !== false) {
            return $this->getNetworkBlanks();
        }
        // Web topics
        elseif (strpos($topic, 'web') !== false || strpos($topic, 'html') !== false) {
            return $this->getWebBlanks();
        }
        // Default: create topic-specific blanks
        else {
            return $this->createDynamicTopicBlanks($topic, $num);
        }
    }

    // Ensure uniqueness in generated templates - FIXED: Handle empty arrays
    private function ensureUniqueness(array $templates, int $num): array
    {
        if (empty($templates)) {
            return [];
        }

        $unique = [];
        $usedWords = [];
        $usedStructures = [];

        foreach ($templates as $template) {
            // Validate template structure
            if (!isset($template['sentence']) || !isset($template['blank_word']) || !isset($template['hint'])) {
                continue;
            }

            $word = strtolower(trim($template['blank_word']));
            $structure = $this->getSentenceStructure($template['sentence']);

            if (!in_array($word, $usedWords) && !in_array($structure, $usedStructures)) {
                $unique[] = $template;
                $usedWords[] = $word;
                $usedStructures[] = $structure;
            }

            if (count($unique) >= $num) {
                break;
            }
        }

        return $unique;
    }

    // Get sentence structure pattern
    private function getSentenceStructure(string $sentence): string
    {
        // Remove the blank and specific words to get structure pattern
        $pattern = preg_replace('/_____/', 'BLANK', $sentence);
        $pattern = preg_replace('/\b\w+\b/', 'WORD', $pattern);
        return $pattern;
    }

    // Create dynamic blanks for any topic
    private function createDynamicTopicBlanks(string $topic, int $num): array
    {
        $blanks = [];
        $baseTemplates = [
            "The concept of _____ is fundamental to understanding {$topic}.",
            "In {$topic}, _____ plays a crucial role in the overall process.",
            "Understanding _____ is essential for mastering {$topic}.",
            "The principle of _____ forms the basis of {$topic} theory.",
            "Advanced applications of {$topic} often involve _____.",
            "The _____ approach is commonly used in {$topic} methodologies.",
            "Professionals working with {$topic} must understand _____.",
            "The development of {$topic} was influenced by _____.",
            "Practical implementation of {$topic} requires knowledge of _____.",
            "The relationship between _____ and {$topic} is well-established.",
        ];

        $conceptWords = [
            ['word' => 'core principles', 'hint' => 'Fundamental concepts'],
            ['word' => 'key methodologies', 'hint' => 'Important methods'],
            ['word' => 'theoretical foundations', 'hint' => 'Basic theories'],
            ['word' => 'practical applications', 'hint' => 'Real-world uses'],
            ['word' => 'advanced techniques', 'hint' => 'Sophisticated methods'],
            ['word' => 'fundamental concepts', 'hint' => 'Basic ideas'],
            ['word' => 'critical components', 'hint' => 'Essential parts'],
            ['word' => 'underlying mechanisms', 'hint' => 'Basic processes'],
            ['word' => 'primary objectives', 'hint' => 'Main goals'],
            ['word' => 'significant developments', 'hint' => 'Important advances'],
        ];

        $count = min($num, count($baseTemplates), count($conceptWords));

        for ($i = 0; $i < $count; $i++) {
            $blanks[] = [
                'sentence' => $baseTemplates[$i],
                'blank_word' => $conceptWords[$i]['word'],
                'hint' => $conceptWords[$i]['hint']
            ];
        }

        return $blanks;
    }

    // Template methods - FIXED: Always return arrays
    private function getLoopProgrammingBlanks(): array
    {
        return [
            ['sentence' => 'A _____ is a control structure that repeats a block of code.', 'blank_word' => 'loop', 'hint' => 'Repetition structure'],
            ['sentence' => 'The _____ loop checks the condition before executing the code.', 'blank_word' => 'while', 'hint' => 'Pre-test loop'],
            ['sentence' => 'A _____ loop executes at least once before checking the condition.', 'blank_word' => 'do-while', 'hint' => 'Post-test loop'],
            ['sentence' => 'The _____ loop is commonly used when the number of iterations is known.', 'blank_word' => 'for', 'hint' => 'Counter-based loop'],
            ['sentence' => 'The _____ statement terminates a loop immediately.', 'blank_word' => 'break', 'hint' => 'Exit loop statement'],
            ['sentence' => 'The _____ statement skips the current iteration and moves to the next.', 'blank_word' => 'continue', 'hint' => 'Skip iteration statement'],
            ['sentence' => 'A _____ loop never stops executing because its condition is always true.', 'blank_word' => 'infinite', 'hint' => 'Never-ending loop'],
            ['sentence' => 'A _____ loop is a loop inside another loop.', 'blank_word' => 'nested', 'hint' => 'Loop within loop'],
            ['sentence' => 'Loop _____ refers to the number of times a loop executes.', 'blank_word' => 'iteration', 'hint' => 'Single execution cycle'],
            ['sentence' => 'The loop _____ determines when the loop should stop.', 'blank_word' => 'condition', 'hint' => 'Boolean expression'],
        ];
    }

    private function getProgrammingBlanks(): array
    {
        return [
            ['sentence' => 'A _____ is a named block of reusable code.', 'blank_word' => 'function', 'hint' => 'Code block'],
            ['sentence' => 'An _____ is a step-by-step procedure to solve a problem.', 'blank_word' => 'algorithm', 'hint' => 'Problem-solving method'],
            ['sentence' => 'A _____ stores data values during program execution.', 'blank_word' => 'variable', 'hint' => 'Data container'],
            ['sentence' => 'The _____ data structure follows Last In First Out.', 'blank_word' => 'stack', 'hint' => 'LIFO structure'],
            ['sentence' => 'A _____ is a blueprint for creating objects.', 'blank_word' => 'class', 'hint' => 'Object template'],
            ['sentence' => 'Exception _____ manages runtime errors gracefully.', 'blank_word' => 'handling', 'hint' => 'Error management'],
            ['sentence' => 'A _____ statement makes decisions in code.', 'blank_word' => 'conditional', 'hint' => 'Decision structure'],
            ['sentence' => 'Code _____ makes programs easier to understand.', 'blank_word' => 'documentation', 'hint' => 'Explanatory text'],
            ['sentence' => 'A _____ is a container that stores multiple values.', 'blank_word' => 'array', 'hint' => 'Collection structure'],
            ['sentence' => 'Object _____ allows classes to inherit properties.', 'blank_word' => 'inheritance', 'hint' => 'OOP principle'],
        ];
    }

    private function getDatabaseBlanks(): array
    {
        return [
            ['sentence' => 'A _____ key uniquely identifies each table record.', 'blank_word' => 'primary', 'hint' => 'Unique identifier'],
            ['sentence' => 'The _____ command retrieves data from tables.', 'blank_word' => 'SELECT', 'hint' => 'Query command'],
            ['sentence' => 'A _____ establishes relationships between tables.', 'blank_word' => 'foreign key', 'hint' => 'Table link'],
            ['sentence' => 'Database _____ organizes data efficiently.', 'blank_word' => 'normalization', 'hint' => 'Data organization'],
            ['sentence' => 'A _____ is a saved SQL query result.', 'blank_word' => 'view', 'hint' => 'Virtual table'],
            ['sentence' => 'The _____ clause filters query results.', 'blank_word' => 'WHERE', 'hint' => 'Filter condition'],
            ['sentence' => 'A _____ ensures data consistency.', 'blank_word' => 'transaction', 'hint' => 'Atomic operation'],
            ['sentence' => 'An _____ speeds up data retrieval.', 'blank_word' => 'index', 'hint' => 'Search optimizer'],
        ];
    }

    private function getNetworkBlanks(): array
    {
        return [
            ['sentence' => 'The _____ protocol ensures reliable transmission.', 'blank_word' => 'TCP', 'hint' => 'Connection-oriented protocol'],
            ['sentence' => 'A _____ forwards packets between networks.', 'blank_word' => 'router', 'hint' => 'Network device'],
            ['sentence' => 'The _____ address identifies network devices.', 'blank_word' => 'IP', 'hint' => 'Internet Protocol'],
            ['sentence' => 'A _____ blocks unauthorized network access.', 'blank_word' => 'firewall', 'hint' => 'Security system'],
            ['sentence' => 'The _____ layer handles end-to-end communication.', 'blank_word' => 'Transport', 'hint' => 'OSI layer'],
            ['sentence' => 'A _____ translates domain names to IPs.', 'blank_word' => 'DNS', 'hint' => 'Name system'],
        ];
    }

    private function getWebBlanks(): array
    {
        return [
            ['sentence' => 'The _____ language structures web content.', 'blank_word' => 'HTML', 'hint' => 'Markup language'],
            ['sentence' => '_____ styles and designs web pages.', 'blank_word' => 'CSS', 'hint' => 'Style sheets'],
            ['sentence' => '_____ adds interactivity to websites.', 'blank_word' => 'JavaScript', 'hint' => 'Scripting language'],
            ['sentence' => 'The _____ method sends form data.', 'blank_word' => 'POST', 'hint' => 'HTTP method'],
            ['sentence' => 'A _____ framework simplifies front-end work.', 'blank_word' => 'Bootstrap', 'hint' => 'CSS framework'],
            ['sentence' => 'Web _____ store data in browsers.', 'blank_word' => 'cookies', 'hint' => 'Browser storage'],
        ];
    }

    private function getArrayBlanks(): array
    {
        return [
            ['sentence' => 'An _____ is a collection of elements stored in contiguous memory.', 'blank_word' => 'array', 'hint' => 'Data structure'],
            ['sentence' => 'Array _____ starts from 0 in most programming languages.', 'blank_word' => 'indexing', 'hint' => 'Element position'],
            ['sentence' => 'A _____ array has rows and columns.', 'blank_word' => 'two-dimensional', 'hint' => 'Matrix structure'],
            ['sentence' => 'The _____ of an array is the number of elements it contains.', 'blank_word' => 'length', 'hint' => 'Size property'],
            ['sentence' => 'Array _____ adds an element to the end.', 'blank_word' => 'push', 'hint' => 'Insertion method'],
        ];
    }

    private function getFunctionBlanks(): array
    {
        return [
            ['sentence' => 'A _____ is a reusable block of code.', 'blank_word' => 'function', 'hint' => 'Code module'],
            ['sentence' => 'Function _____ are values passed to a function.', 'blank_word' => 'parameters', 'hint' => 'Input values'],
            ['sentence' => 'The _____ statement sends a value back from a function.', 'blank_word' => 'return', 'hint' => 'Output keyword'],
            ['sentence' => 'A _____ function has no name.', 'blank_word' => 'anonymous', 'hint' => 'Nameless function'],
            ['sentence' => 'Function _____ is calling a function within itself.', 'blank_word' => 'recursion', 'hint' => 'Self-calling'],
        ];
    }

    private function getOOPBlanks(): array
    {
        return [
            ['sentence' => 'A _____ is a blueprint for creating objects.', 'blank_word' => 'class', 'hint' => 'Object template'],
            ['sentence' => 'An _____ is an instance of a class.', 'blank_word' => 'object', 'hint' => 'Class instance'],
            ['sentence' => 'Class _____ are variables that store object data.', 'blank_word' => 'attributes', 'hint' => 'Object properties'],
            ['sentence' => 'Class _____ are functions defined inside a class.', 'blank_word' => 'methods', 'hint' => 'Object behaviors'],
            ['sentence' => '_____ allows a class to inherit from another class.', 'blank_word' => 'Inheritance', 'hint' => 'Code reuse principle'],
        ];
    }

    private function getPythonBlanks(): array
    {
        return [
            ['sentence' => '_____ is a high-level interpreted programming language.', 'blank_word' => 'Python', 'hint' => 'Popular language'],
            ['sentence' => 'Python uses _____ for code blocks instead of braces.', 'blank_word' => 'indentation', 'hint' => 'Code structure'],
            ['sentence' => 'A Python _____ is an ordered collection of items.', 'blank_word' => 'list', 'hint' => 'Mutable sequence'],
            ['sentence' => 'A Python _____ is an immutable sequence of values.', 'blank_word' => 'tuple', 'hint' => 'Fixed sequence'],
            ['sentence' => 'A Python _____ stores key-value pairs.', 'blank_word' => 'dictionary', 'hint' => 'Associative array'],
        ];
    }

    private function getJavaBlanks(): array
    {
        return [
            ['sentence' => '_____ is an object-oriented programming language.', 'blank_word' => 'Java', 'hint' => 'Platform-independent'],
            ['sentence' => 'The _____ method is the entry point of a Java program.', 'blank_word' => 'main', 'hint' => 'Starting method'],
            ['sentence' => 'Java code is compiled into _____ that runs on JVM.', 'blank_word' => 'bytecode', 'hint' => 'Intermediate code'],
            ['sentence' => 'The _____ keyword creates a new object in Java.', 'blank_word' => 'new', 'hint' => 'Object creation'],
            ['sentence' => 'Java _____ group related classes together.', 'blank_word' => 'packages', 'hint' => 'Class organization'],
        ];
    }
}
