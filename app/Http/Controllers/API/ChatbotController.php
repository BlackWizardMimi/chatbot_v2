<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        set_time_limit(0); // 2 minutes

        $prompt = strtolower($request->input('message'));

        // 0. FAQ normal seperti jam berapa buka?, hi!, dll → see database
        $faq = Faq::where('question', 'like', "%$prompt%")->first();
        if (!empty($faq)) {
            return response()->json(['reply' => $faq->answer]);
        }

        //ini menggunakan fix mode CRUD untuk customer
        // 1. Insert (Create)
        if (str_contains($prompt, 'add customer')) {
            preg_match('/add customer (.+) with email (.+)/', $prompt, $m);
            if (count($m) === 3) {
                $c = Customer::create([
                    'name' => $m[1],
                    'email' => $m[2],
                ]);
                return response()->json(['reply' => "Customer {$c->name} added."]);
            }
        }

        // 2. Get (Read)
        if (str_contains($prompt, 'list customers')) {
            $customers = Customer::all()->pluck('name')->toArray();
            return response()->json(['reply' => "Customers: " . implode(', ', $customers)]);
        }

        // 3. Update
        if (str_contains($prompt, 'update customer')) {
            preg_match('/update customer (.+) email to (.+)/', $prompt, $m);
            if (count($m) === 3) {
                $c = Customer::where('name', $m[1])->first();
                if ($c) {
                    $c->email = $m[2];
                    $c->save();
                    return response()->json(['reply' => "Customer {$c->name} updated."]);
                }
                return response()->json(['reply' => "Customer not found."]);
            }
        }

        // 4. Delete
        if (str_contains($prompt, 'delete customer')) {
            preg_match('/delete customer (.+)/', $prompt, $m);
            if (count($m) === 2) {
                $c = Customer::where('name', $m[1])->first();
                if ($c) {
                    $c->delete();
                    return response()->json(['reply' => "Customer {$m[1]} deleted."]);
                }
                return response()->json(['reply' => "Customer not found."]);
            }
        }

        //ini menggunakan AI mode membantu CRUD untuk customer, Proses AI cukup lama menggunakan local dalam Demo
        // 5: Ask AI to parse CRUD
        $schema = `You are an AI assistant that converts natural language user requests into structured JSON commands for CRUD operations on a database.

        Available tables:
        - customers: {id, name, email}
        
        Allowed actions: create, read, update, delete
        
        Rules:
        1. Always output JSON only. Do not include explanations.
        2. JSON must contain at least:
           - "action": one of create, read, update, delete, or "none" if not CRUD
           - "table": the target table name
           - Any relevant fields needed for that action (like name, email, product, quantity, etc.)
        3. If the user request is not related to CRUD, return:
           {"action":"none"}
        
        Examples:
        
        User: add customer John with email john@example.com
        Output: {"action":"create","table":"customers","name":"John","email":"john@example.com"}
        
        User: update customer John email to john123@example.com
        Output: {"action":"update","table":"customers","name":"John","email":"john123@example.com"}
        
        User: delete customer John
        Output: {"action":"delete","table":"customers","name":"John"}
        
        User: show all customers
        Output: {"action":"read","table":"customers"}

        User: list all customers
        Output: {"action":"read","table":"customers"}
        
        User: tambahkan kustomer Jimmy email jimmy@gmail.com
        Output: {"action":"create","table":"customers","name":"Jimmy","email":"jimmy@gmail.com"}
        
        User: tampilkan semua kustomer
        Output: {"action":"read","table":"customers"}
        
        User: I want to know today's weather
        Output: {"action":"none"}
        
        Now convert this user request into JSON:
        
        User: <USER_INPUT>
        Output:
        '`;

        $instruction = "Convert this request into JSON command for CRUD.\n" .
                    $schema . "\n\nUser: $prompt\nJSON:";

        $json = $this->askLlama($instruction);
        $data = json_decode($json, true);

        // 6: If AI says CRUD, execute
        if (isset($data['action']) && $data['action'] !== "none") {
            return response()->json($this->executeCrud($data));
        }

        //ini menggunakan AI mode sebagai business consultation chatbot,  Proses AI cukup lama menggunakan local dalam Demo
        // 7: Fallback → business consultation chatbot
        $reply = $this->askLlama("You are a business consultant. " . $prompt);
        return response()->json(['reply' => $reply]);
        

    }

    public function askLlama($prompt)
    {
        try {
            $response = Http::timeout(1000)->post("http://127.0.0.1:11434/api/generate", [
                "model" => "llama3",
                "prompt" => $prompt,
                "stream" => false // important → get full JSON, not streaming chunks
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? "I don't understand.";
            }

            return "Error: AI service did not respond.";
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    private function executeCrud($data)
    {
        switch ($data['action']) {
            case 'create':
                $c = Customer::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);
                return ['reply' => "Customer {$c->name} added."];

            case 'read':
                $customers = Customer::all()->pluck('name')->toArray();
                return ['reply' => "Customers: " . implode(', ', $customers)];

            case 'update':
                $c = Customer::where('id', $data['id'])->first();
                if ($c) {
                    $c->update(collect($data)->except(['action','table','id'])->toArray());
                    return ['reply' => "Customer {$c->name} updated."];
                }
                return ['reply' => "Customer not found."];

            case 'delete':
                $c = Customer::where('id', $data['id'])->first();
                if ($c) {
                    $c->delete();
                    return ['reply' => "Customer {$data['id']} deleted."];
                }
                return ['reply' => "Customer not found."];

            default:
                return ['reply' => "Unknown action"];
        }
    }


}
