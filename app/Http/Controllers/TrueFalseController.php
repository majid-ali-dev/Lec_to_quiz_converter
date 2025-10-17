<?php

namespace App\Http\Controllers;

use App\Models\TrueFalse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class TrueFalseController extends Controller
{
    public function index()
    {
        return view('true-false.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'university_name' => 'required|string|max:255',
            'topic_name' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:5|max:30',
        ]);

        try {
            $items = $this->generateWithGroq($request->topic_name, $request->num_questions);
            $model = TrueFalse::create([
                'university_name' => $request->university_name,
                'topic_name' => $request->topic_name,
                'num_questions' => $request->num_questions,
                'statements' => $items,
            ]);
            return redirect()->route('truefalse.result', $model->id);
        } catch (\Exception $e) {
            Log::error('TrueFalse Generation Error: ' . $e->getMessage());
            return back()->with('error', 'Generation failed. Please try again.');
        }
    }

    public function showResult($id)
    {
        $data = TrueFalse::findOrFail($id);
        return view('true-false.result', compact('data'));
    }

    public function downloadPDF($id)
    {
        $data = TrueFalse::findOrFail($id);
        $pdf = Pdf::loadView('true-false.pdf', compact('data'));
        return $pdf->download('true_false_' . str_replace(' ', '_', $data->topic_name) . '.pdf');
    }

    public function downloadDOCX($id)
    {
        $data = TrueFalse::findOrFail($id);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText($data->university_name . ' - True/False', ['bold' => true, 'size' => 16]);
        $section->addText('Topic: ' . $data->topic_name);
        $section->addTextBreak(1);
        foreach ($data->statements as $index => $s) {
            $number = $index + 1;
            $section->addText($number . '. ' . ($s['statement'] ?? ''));
        }
        $fileName = 'true_false_' . str_replace(' ', '_', $data->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function downloadAnswerKeyPDF($id)
    {
        $data = TrueFalse::findOrFail($id);
        $pdf = Pdf::loadView('true-false.answer-key', compact('data'));
        return $pdf->download('true_false_key_' . str_replace(' ', '_', $data->topic_name) . '.pdf');
    }

    public function downloadAnswerKeyDOCX($id)
    {
        $data = TrueFalse::findOrFail($id);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('CONFIDENTIAL - Answer Key', ['bold' => true, 'size' => 16]);
        $section->addText('Topic: ' . $data->topic_name);
        $section->addTextBreak(1);
        foreach ($data->statements as $index => $s) {
            $number = $index + 1;
            $ans = isset($s['answer']) && $s['answer'] ? 'True' : 'False';
            $ex = $s['explanation'] ?? '';
            $section->addText('Q' . $number . ': ' . $ans . ' - ' . $ex);
        }
        $fileName = 'true_false_key_' . str_replace(' ', '_', $data->topic_name) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    // Generate questions with Groq API - ENHANCED VERSION
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
                    Log::info('Successfully generated high-quality True/False via Groq API');
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
        $prompt = "CRITICAL INSTRUCTIONS FOR TRUE/FALSE QUESTIONS ABOUT: '{$topic}'

STRICT REQUIREMENTS:
1. Generate EXACTLY {$num} UNIQUE, NON-REPETITIVE True/False statements
2. EVERY statement MUST be SPECIFICALLY about '{$topic}' - NO generic statements
3. Create a BALANCED mix of True and False statements (approximately 50/50)
4. All statements must be FACTUALLY ACCURATE and EDUCATIONAL
5. False statements should be PLAUSIBLE but factually incorrect
6. Avoid ambiguous or opinion-based statements
7. Focus on KEY CONCEPTS, TERMINOLOGY, and IMPORTANT FACTS about '{$topic}'
8. Ensure statements are CLEAR and UNAMBIGUOUS

QUALITY CONTROL:
- No repeated concepts
- No trivial statements
- All explanations must be accurate and informative
- Statements should test actual knowledge of '{$topic}'

OUTPUT FORMAT: STRICT JSON array only
Each object must have: 
{
  \"statement\": \"Clear and concise statement\",
  \"answer\": true/false,
  \"explanation\": \"Brief factual explanation\"
}

EXAMPLES for topic 'Artificial Intelligence':
{
  \"statement\": \"Machine learning is a subset of artificial intelligence.\",
  \"answer\": true,
  \"explanation\": \"Machine learning is indeed a subfield of AI focused on algorithms that learn from data.\"
}
{
  \"statement\": \"Neural networks were first developed in the 21st century.\",
  \"answer\": false, 
  \"explanation\": \"Neural networks were first proposed in the 1940s and developed throughout the 20th century.\"
}

NOW generate {$num} HIGH-QUALITY, UNIQUE True/False statements specifically about '{$topic}':";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            'Content-Type' => 'application/json',
        ])->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educator specializing in creating high-quality, unique True/False questions. You strictly follow all instructions and never repeat concepts. You focus on meaningful, educational content that tests actual knowledge.'
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

        $trueCount = 0;
        $falseCount = 0;
        $usedStatements = [];
        $usedConcepts = [];

        foreach ($items as $index => $item) {
            // Check required fields
            if (empty($item['statement']) || !isset($item['answer']) || empty($item['explanation'])) {
                Log::warning("Missing required fields in item {$index}");
                return false;
            }

            $statement = trim($item['statement']);
            $answer = $item['answer'];
            $explanation = trim($item['explanation']);

            // Check statement length and quality
            if (strlen($statement) < 10) {
                Log::warning("Statement too short: {$statement}");
                return false;
            }

            // Check for duplicate statements
            $statementKey = md5(strtolower($statement));
            if (in_array($statementKey, $usedStatements)) {
                Log::warning("Duplicate statement: {$statement}");
                return false;
            }
            $usedStatements[] = $statementKey;

            // Check answer is boolean
            if (!is_bool($answer)) {
                Log::warning("Answer is not boolean");
                return false;
            }

            // Count true/false for balance check
            if ($answer) {
                $trueCount++;
            } else {
                $falseCount++;
            }

            // Check explanation quality
            if (strlen($explanation) < 10) {
                Log::warning("Explanation too short: {$explanation}");
                return false;
            }
        }

        // Ensure reasonable balance (not all true or all false)
        if ($trueCount === 0 || $falseCount === 0) {
            Log::warning("No balance in true/false statements");
            return false;
        }

        return true;
    }

    // Check for duplicate questions
    private function hasDuplicates(array $items): bool
    {
        $statements = [];

        foreach ($items as $item) {
            $statement = strtolower(trim($item['statement'] ?? ''));
            if (in_array($statement, $statements)) {
                return true;
            }
            $statements[] = $statement;
        }

        return false;
    }

    // Enhanced smart generator with topic analysis
    private function smartGenerateWithTopic(string $topic, int $num): array
    {
        $topicLower = strtolower(trim($topic));

        // Analyze topic and get appropriate templates
        $templates = $this->analyzeTopicAndGetTemplates($topicLower, $num);

        // Ensure we always have an array
        if (!is_array($templates) || empty($templates)) {
            Log::warning('No templates found, using dynamic generation');
            $templates = $this->createDynamicTopicStatements($topic, $num);
        }

        // Ensure uniqueness
        $uniqueTemplates = $this->ensureUniqueness($templates, $num);

        return array_slice($uniqueTemplates, 0, $num);
    }

    // Analyze topic and get relevant templates
    private function analyzeTopicAndGetTemplates(string $topic, int $num): array
    {
        // Programming topics
        if (strpos($topic, 'loop') !== false) {
            return $this->getLoopProgrammingStatements();
        } elseif (strpos($topic, 'python') !== false) {
            return $this->getPythonStatements();
        } elseif (strpos($topic, 'java') !== false) {
            return $this->getJavaStatements();
        } elseif (strpos($topic, 'array') !== false) {
            return $this->getArrayStatements();
        } elseif (strpos($topic, 'function') !== false) {
            return $this->getFunctionStatements();
        } elseif (strpos($topic, 'object') !== false || strpos($topic, 'oop') !== false) {
            return $this->getOOPStatements();
        } elseif (strpos($topic, 'programming') !== false || strpos($topic, 'code') !== false) {
            return $this->getProgrammingStatements();
        }
        // Database topics
        elseif (strpos($topic, 'database') !== false || strpos($topic, 'sql') !== false) {
            return $this->getDatabaseStatements();
        }
        // Network topics
        elseif (strpos($topic, 'network') !== false || strpos($topic, 'internet') !== false) {
            return $this->getNetworkStatements();
        }
        // Web topics
        elseif (strpos($topic, 'web') !== false || strpos($topic, 'html') !== false) {
            return $this->getWebStatements();
        }
        // Default: create topic-specific statements
        else {
            return $this->createDynamicTopicStatements($topic, $num);
        }
    }

    // Ensure uniqueness in generated templates
    private function ensureUniqueness(array $templates, int $num): array
    {
        if (empty($templates)) {
            return [];
        }

        $unique = [];
        $usedStatements = [];

        foreach ($templates as $template) {
            // Validate template structure
            if (!isset($template['statement']) || !isset($template['answer']) || !isset($template['explanation'])) {
                continue;
            }

            $statement = strtolower(trim($template['statement']));

            if (!in_array($statement, $usedStatements)) {
                $unique[] = $template;
                $usedStatements[] = $statement;
            }

            if (count($unique) >= $num) {
                break;
            }
        }

        return $unique;
    }

    // Create dynamic statements for any topic
    private function createDynamicTopicStatements(string $topic, int $num): array
    {
        $statements = [];
        $baseStatements = [
            [
                'statement' => "The concept of {$topic} was first developed in the 21st century.",
                'answer' => false,
                'explanation' => "The fundamental concepts of {$topic} were established much earlier in academic research."
            ],
            [
                'statement' => "Understanding {$topic} requires knowledge of its core principles.",
                'answer' => true,
                'explanation' => "Core principles form the foundation for advanced understanding of {$topic}."
            ],
            [
                'statement' => "{$topic} is primarily used for entertainment purposes only.",
                'answer' => false,
                'explanation' => "{$topic} has serious applications across various industries and research fields."
            ],
            [
                'statement' => "The theoretical basis of {$topic} is well-established in academic literature.",
                'answer' => true,
                'explanation' => "Numerous research papers and textbooks cover the theoretical foundations of {$topic}."
            ],
            [
                'statement' => "{$topic} can be fully automated without human intervention.",
                'answer' => false,
                'explanation' => "Human expertise and oversight remain crucial in the application of {$topic}."
            ],
            [
                'statement' => "Professional certification is available for {$topic} practitioners.",
                'answer' => true,
                'explanation' => "Various organizations offer certification programs for {$topic} professionals."
            ],
            [
                'statement' => "{$topic} is only relevant to computer science fields.",
                'answer' => false,
                'explanation' => "{$topic} has applications across multiple disciplines including business, healthcare, and engineering."
            ],
            [
                'statement' => "Research in {$topic} continues to evolve with new developments.",
                'answer' => true,
                'explanation' => "Ongoing research constantly expands the boundaries and applications of {$topic}."
            ],
            [
                'statement' => "{$topic} requires expensive specialized equipment to implement.",
                'answer' => false,
                'explanation' => "Many aspects of {$topic} can be implemented using commonly available tools and platforms."
            ],
            [
                'statement' => "The principles of {$topic} are taught in university programs worldwide.",
                'answer' => true,
                'explanation' => "Higher education institutions globally include {$topic} in their curriculum."
            ],
        ];

        $count = min($num, count($baseStatements));

        for ($i = 0; $i < $count; $i++) {
            $statements[] = $baseStatements[$i];
        }

        return $statements;
    }

    // Template methods for different topics
    private function getLoopProgrammingStatements(): array
    {
        return [
            ['statement' => 'A while loop checks the condition after executing the code block.', 'answer' => false, 'explanation' => 'A while loop checks the condition before executing the code block.'],
            ['statement' => 'Infinite loops occur when the loop condition never becomes false.', 'answer' => true, 'explanation' => 'Infinite loops continue indefinitely because the termination condition is never met.'],
            ['statement' => 'A for loop is used when the number of iterations is unknown.', 'answer' => false, 'explanation' => 'A for loop is typically used when the number of iterations is known beforehand.'],
            ['statement' => 'The break statement terminates the entire loop immediately.', 'answer' => true, 'explanation' => 'The break statement exits the loop completely, regardless of the condition.'],
            ['statement' => 'Nested loops cannot use the same loop variable name.', 'answer' => false, 'explanation' => 'Nested loops can use the same variable name if they are in different scopes.'],
            ['statement' => 'A do-while loop executes at least once.', 'answer' => true, 'explanation' => 'The do-while loop executes the code block first, then checks the condition.'],
            ['statement' => 'All programming languages support the same types of loops.', 'answer' => false, 'explanation' => 'Different programming languages support different types and variations of loops.'],
            ['statement' => 'Loop unrolling is an optimization technique.', 'answer' => true, 'explanation' => 'Loop unrolling reduces overhead by executing multiple iterations in a single cycle.'],
        ];
    }

    private function getProgrammingStatements(): array
    {
        return [
            ['statement' => 'Object-oriented programming supports inheritance.', 'answer' => true, 'explanation' => 'Inheritance is a fundamental principle of object-oriented programming.'],
            ['statement' => 'All programming languages are compiled languages.', 'answer' => false, 'explanation' => 'Some languages are interpreted (like Python) while others are compiled (like C++).'],
            ['statement' => 'A variable can store multiple data types simultaneously.', 'answer' => false, 'explanation' => 'A variable typically stores one data type at a time in statically typed languages.'],
            ['statement' => 'Functions help in code reusability.', 'answer' => true, 'explanation' => 'Functions allow code to be written once and reused multiple times.'],
            ['statement' => 'Arrays can only store numeric values.', 'answer' => false, 'explanation' => 'Arrays can store various data types including strings, objects, and other arrays.'],
        ];
    }

    private function getDatabaseStatements(): array
    {
        return [
            ['statement' => 'A primary key can contain null values.', 'answer' => false, 'explanation' => 'Primary keys must have unique, non-null values.'],
            ['statement' => 'SQL is used for querying relational databases.', 'answer' => true, 'explanation' => 'SQL (Structured Query Language) is designed for managing relational databases.'],
            ['statement' => 'Normalization reduces data redundancy.', 'answer' => true, 'explanation' => 'Database normalization organizes data to minimize duplication.'],
            ['statement' => 'NoSQL databases require fixed schemas.', 'answer' => false, 'explanation' => 'NoSQL databases typically have flexible, dynamic schemas.'],
        ];
    }

    private function getNetworkStatements(): array
    {
        return [
            ['statement' => 'TCP provides reliable data delivery.', 'answer' => true, 'explanation' => 'TCP ensures reliable, ordered delivery of data packets.'],
            ['statement' => 'HTTP is a secure protocol by default.', 'answer' => false, 'explanation' => 'HTTP is not secure; HTTPS provides security through encryption.'],
            ['statement' => 'DNS translates domain names to IP addresses.', 'answer' => true, 'explanation' => 'Domain Name System resolves human-readable names to IP addresses.'],
        ];
    }

    private function getWebStatements(): array
    {
        return [
            ['statement' => 'HTML is a programming language.', 'answer' => false, 'explanation' => 'HTML is a markup language, not a programming language.'],
            ['statement' => 'CSS is used for styling web pages.', 'answer' => true, 'explanation' => 'CSS controls the presentation and layout of web content.'],
            ['statement' => 'JavaScript can only run in web browsers.', 'answer' => false, 'explanation' => 'JavaScript can run on servers (Node.js) and other environments.'],
        ];
    }

    private function getArrayStatements(): array
    {
        return [
            ['statement' => 'Array indices start at 1 in most programming languages.', 'answer' => false, 'explanation' => 'Most programming languages use zero-based indexing for arrays.'],
            ['statement' => 'Arrays can be multidimensional.', 'answer' => true, 'explanation' => 'Arrays can have multiple dimensions (2D, 3D, etc.).'],
        ];
    }

    private function getFunctionStatements(): array
    {
        return [
            ['statement' => 'All functions must return a value.', 'answer' => false, 'explanation' => 'Functions can be void and not return any value.'],
            ['statement' => 'Recursive functions call themselves.', 'answer' => true, 'explanation' => 'Recursion involves functions calling themselves to solve problems.'],
        ];
    }

    private function getOOPStatements(): array
    {
        return [
            ['statement' => 'Encapsulation hides implementation details.', 'answer' => true, 'explanation' => 'Encapsulation bundles data and methods while hiding internal details.'],
            ['statement' => 'Inheritance allows code reuse.', 'answer' => true, 'explanation' => 'Inheritance enables new classes to adopt properties of existing classes.'],
        ];
    }

    private function getPythonStatements(): array
    {
        return [
            ['statement' => 'Python uses braces {} for code blocks.', 'answer' => false, 'explanation' => 'Python uses indentation to define code blocks, not braces.'],
            ['statement' => 'Python lists are mutable.', 'answer' => true, 'explanation' => 'List elements can be modified after creation.'],
        ];
    }

    private function getJavaStatements(): array
    {
        return [
            ['statement' => 'Java is a compiled language.', 'answer' => true, 'explanation' => 'Java code is compiled to bytecode that runs on the JVM.'],
            ['statement' => 'Java supports multiple inheritance.', 'answer' => false, 'explanation' => 'Java supports single inheritance of classes but multiple inheritance of interfaces.'],
        ];
    }
}
