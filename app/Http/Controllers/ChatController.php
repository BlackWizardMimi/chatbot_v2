<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    /**
     * Handle regular LLaMA chat
     */
    public function chat(Request $request)
    {
        $message = $request->input('message');
        
        try {
            // Call your existing LLaMA API here
            // This is where you'd integrate with your local LLaMA
            $response = $this->callLlamaAPI($message);
            
            return response()->json([
                'reply' => $response
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Sorry, I encountered an error processing your request.'
            ], 500);
        }
    }
    
    /**
     * Handle business intelligence chat with CRUD operations
     */
    public function businessChat(Request $request)
    {
        $message = $request->input('message');
        $prompt = strtolower($message);
        
        try {
            // CRUD Operations First (exact match with your existing code)
            
            // 1. Insert (Create)
            if (str_contains($prompt, 'add customer')) {
                preg_match('/add customer (.+) with email (.+)/', $prompt, $m);
                if (count($m) === 3) {
                    $c = Customer::create([
                        'name' => $m[1],
                        'email' => $m[2],
                    ]);
                    return response()->json(['reply' => "✅ Customer {$c->name} added successfully! You now have " . Customer::count() . " total customers."]);
                }
                return response()->json(['reply' => "❌ Please use format: 'add customer [Name] with email [email@example.com]'"]);
            }
            
            // 2. Get (Read)
            if (str_contains($prompt, 'list customers')) {
                $customers = Customer::all();
                if ($customers->count() === 0) {
                    return response()->json(['reply' => "📝 No customers found. Try adding some with: 'add customer John with email john@example.com'"]);
                }
                
                $customerList = "👥 **CUSTOMER LIST** ({$customers->count()} total):\n\n";
                foreach ($customers as $index => $customer) {
                    $number = $index + 1;
                    $phone = !empty($customer->phone) ? " 📱 {$customer->phone}" : " ❌ No phone";
                    $customerList .= "{$number}. **{$customer->name}**\n";
                    $customerList .= "   📧 {$customer->email}{$phone}\n";
                    $customerList .= "   📅 Added: " . $customer->created_at->format('M d, Y') . "\n\n";
                }
                
                return response()->json(['reply' => $customerList]);
            }
            
            // 3. Update
            if (str_contains($prompt, 'update customer')) {
                preg_match('/update customer (.+) email to (.+)/', $prompt, $m);
                if (count($m) === 3) {
                    $c = Customer::where('name', 'like', '%' . $m[1] . '%')->first();
                    if ($c) {
                        $oldEmail = $c->email;
                        $c->email = $m[2];
                        $c->save();
                        return response()->json(['reply' => "✅ Customer **{$c->name}** updated!\n📧 Email changed from {$oldEmail} to {$c->email}"]);
                    }
                    return response()->json(['reply' => "❌ Customer '{$m[1]}' not found. Use 'list customers' to see all customers."]);
                }
                return response()->json(['reply' => "❌ Please use format: 'update customer [Name] email to [newemail@example.com]'"]);
            }
            
            // 4. Delete
            if (str_contains($prompt, 'delete customer')) {
                preg_match('/delete customer (.+)/', $prompt, $m);
                if (count($m) === 2) {
                    $c = Customer::where('name', 'like', '%' . $m[1] . '%')->first();
                    if ($c) {
                        $customerName = $c->name;
                        $c->delete();
                        $remaining = Customer::count();
                        return response()->json(['reply' => "🗑️ Customer **{$customerName}** deleted successfully!\n👥 You now have {$remaining} customers remaining."]);
                    }
                    return response()->json(['reply' => "❌ Customer '{$m[1]}' not found. Use 'list customers' to see all customers."]);
                }
                return response()->json(['reply' => "❌ Please use format: 'delete customer [Name]'"]);
            }
            
            // Business Intelligence Operations
            if (str_contains($prompt, 'analyz') || str_contains($prompt, 'analysis')) {
                $response = $this->analyzeCustomers();
            } elseif (str_contains($prompt, 'marketing')) {
                $response = $this->getMarketingInsights();
            } elseif (str_contains($prompt, 'recommend') || str_contains($prompt, 'suggestion')) {
                $response = $this->getBusinessRecommendations();
            } elseif (str_contains($prompt, 'stats') || str_contains($prompt, 'quick')) {
                $response = $this->getQuickStats();
            } elseif (str_contains($prompt, 'top customer')) {
                $response = $this->getTopCustomers();
            } else {
                // For other questions, provide help menu
                $response = $this->getHelpMenu();
            }
            
            return response()->json([
                'reply' => $response
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'reply' => '❌ Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Analyze customer base
     */
    private function analyzeCustomers()
    {
        $customers = Customer::all();
        $total = $customers->count();
        
        if ($total === 0) {
            return "📊 No customers found in your database. Start by adding some customers to get insights!";
        }
        
        // Analyze email domains
        $emailDomains = [];
        $businessCustomers = 0;
        $customersWithPhone = 0;
        
        foreach ($customers as $customer) {
            // Count phone numbers
            if (!empty($customer->phone)) {
                $customersWithPhone++;
            }
            
            // Analyze email domains
            if (!empty($customer->email) && str_contains($customer->email, '@')) {
                $domain = strtolower(explode('@', $customer->email)[1]);
                $emailDomains[$domain] = ($emailDomains[$domain] ?? 0) + 1;
                
                // Count business emails
                if (!in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'])) {
                    $businessCustomers++;
                }
            }
        }
        
        // Calculate percentages
        $phonePercentage = round(($customersWithPhone / $total) * 100, 1);
        $businessPercentage = round(($businessCustomers / $total) * 100, 1);
        
        // Create report
        $report = "📊 CUSTOMER ANALYSIS REPORT\n";
        $report .= "══════════════════════════════\n\n";
        $report .= "👥 Total Customers: {$total}\n";
        $report .= "🏢 Business Customers: {$businessCustomers} ({$businessPercentage}%)\n";
        $report .= "📱 Have Phone Numbers: {$customersWithPhone} ({$phonePercentage}%)\n\n";
        $report .= "🏆 Top Email Providers:\n";
        
        // Show top 5 email domains
        arsort($emailDomains);
        $topDomains = array_slice($emailDomains, 0, 5, true);
        
        foreach ($topDomains as $domain => $count) {
            $percentage = round(($count / $total) * 100, 1);
            $report .= "   • {$domain}: {$count} customers ({$percentage}%)\n";
        }
        
        return $report;
    }
    
    /**
     * Generate marketing insights
     */
    private function getMarketingInsights()
    {
        $customers = Customer::all();
        $total = $customers->count();
        
        if ($total === 0) {
            return "📧 No customer data available for marketing analysis.";
        }
        
        $insights = "🎯 MARKETING INSIGHTS\n";
        $insights .= "══════════════════════\n\n";
        
        // Email domain analysis
        $emailDomains = [];
        foreach ($customers as $customer) {
            if (!empty($customer->email) && str_contains($customer->email, '@')) {
                $domain = strtolower(explode('@', $customer->email)[1]);
                $emailDomains[$domain] = ($emailDomains[$domain] ?? 0) + 1;
            }
        }
        
        // Gmail users - Google Ads opportunity
        $gmailUsers = $emailDomains['gmail.com'] ?? 0;
        if ($gmailUsers > 0) {
            $insights .= "📧 {$gmailUsers} Gmail users → Perfect target for Google Ads campaigns\n\n";
        }
        
        // Business email users - B2B opportunity
        $personalDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
        $businessCount = 0;
        foreach ($emailDomains as $domain => $count) {
            if (!in_array($domain, $personalDomains)) {
                $businessCount += $count;
            }
        }
        
        if ($businessCount > 0) {
            $insights .= "🏢 {$businessCount} business email users → Create targeted B2B marketing campaigns\n\n";
        }
        
        // Phone number analysis
        $phoneUsers = $customers->whereNotNull('phone')->where('phone', '!=', '')->count();
        $phonePercentage = round(($phoneUsers / $total) * 100, 1);
        
        if ($phonePercentage < 80) {
            $missing = $total - $phoneUsers;
            $insights .= "📱 Only {$phonePercentage}% have phone numbers → Offer discount/incentive to collect {$missing} missing numbers\n\n";
        }
        
        // Recent customers analysis
        $recentCustomers = $customers->where('created_at', '>=', now()->subDays(30))->count();
        if ($recentCustomers > 0) {
            $insights .= "🆕 {$recentCustomers} new customers (30 days) → Send automated welcome email series\n\n";
        }
        
        $insights .= "💡 Action Items:\n";
        $insights .= "• Launch Google Ads for Gmail users\n";
        $insights .= "• Create B2B landing page for business customers\n";
        $insights .= "• Implement phone collection incentive program";
        
        return $insights;
    }
    
    /**
     * Generate business recommendations
     */
    private function getBusinessRecommendations()
    {
        $customers = Customer::all();
        $total = $customers->count();
        
        $recommendations = "💡 BUSINESS RECOMMENDATIONS\n";
        $recommendations .= "═══════════════════════════════\n\n";
        
        if ($total === 0) {
            return $recommendations . "Start by adding customers to your database to get personalized recommendations!";
        }
        
        // Phone collection priority
        $phoneUsers = $customers->whereNotNull('phone')->where('phone', '!=', '')->count();
        $phonePercentage = ($phoneUsers / $total) * 100;
        
        if ($phonePercentage < 70) {
            $missing = $total - $phoneUsers;
            $recommendations .= "📞 HIGH PRIORITY: {$missing} customers missing phone numbers\n";
            $recommendations .= "   → Offer 10% discount for completing profile\n";
            $recommendations .= "   → Add phone field to checkout process\n\n";
        }
        
        // Business opportunity assessment
        $businessEmails = 0;
        foreach ($customers as $customer) {
            if (!empty($customer->email) && str_contains($customer->email, '@')) {
                $domain = strtolower(explode('@', $customer->email)[1]);
                if (!in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'])) {
                    $businessEmails++;
                }
            }
        }
        
        if ($businessEmails > 5) {
            $recommendations .= "🏢 B2B OPPORTUNITY: {$businessEmails} business customers detected\n";
            $recommendations .= "   → Create enterprise pricing plans\n";
            $recommendations .= "   → Develop B2B-focused marketing materials\n\n";
        }
        
        // Growth stage recommendations
        if ($total < 100) {
            $recommendations .= "📈 GROWTH STAGE: Under 100 customers\n";
            $recommendations .= "   → Focus on customer acquisition\n";
            $recommendations .= "   → Increase marketing budget by 30-50%\n";
            $recommendations .= "   → Implement referral program\n\n";
        } elseif ($total > 500) {
            $recommendations .= "🎯 SCALE STAGE: Large customer base ({$total} customers)\n";
            $recommendations .= "   → Focus on customer retention\n";
            $recommendations .= "   → Implement loyalty program\n";
            $recommendations .= "   → Develop customer success team\n\n";
        }
        
        $recommendations .= "🚀 Next Steps:\n";
        $recommendations .= "1. Improve data collection processes\n";
        $recommendations .= "2. Segment customers for targeted campaigns\n";
        $recommendations .= "3. Set up automated email marketing\n";
        $recommendations .= "4. Track key metrics weekly";
        
        return $recommendations;
    }
    
    /**
     * Get quick statistics
     */
    private function getQuickStats()
    {
        $customers = Customer::all();
        $total = $customers->count();
        
        if ($total === 0) {
            return "📊 No customers in database yet. Add some customers to see statistics!";
        }
        
        $withPhone = $customers->whereNotNull('phone')->where('phone', '!=', '')->count();
        $recentCustomers = $customers->where('created_at', '>=', now()->subDays(7))->count();
        
        // Business emails
        $business = 0;
        foreach ($customers as $customer) {
            if (!empty($customer->email) && str_contains($customer->email, '@')) {
                $domain = strtolower(explode('@', $customer->email)[1]);
                if (!in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'])) {
                    $business++;
                }
            }
        }
        
        return "📊 QUICK STATS OVERVIEW\n" .
               "═══════════════════════\n\n" .
               "👥 Total Customers: {$total}\n" .
               "🏢 Business Customers: {$business}\n" .
               "📱 With Phone Numbers: {$withPhone}\n" .
               "📧 Missing Phone: " . ($total - $withPhone) . "\n" .
               "🆕 New This Week: {$recentCustomers}";
    }
    
    /**
     * Get top customers (most recent or complete profiles)
     */
    private function getTopCustomers()
    {
        $customers = Customer::latest()->limit(10)->get();
        
        if ($customers->count() === 0) {
            return "👑 No customers found. Add some customers first!";
        }
        
        $response = "👑 TOP 10 RECENT CUSTOMERS\n";
        $response .= "═══════════════════════════\n\n";
        
        foreach ($customers as $index => $customer) {
            $number = $index + 1;
            $phone = !empty($customer->phone) ? "📱" : "❌";
            $businessEmail = false;
            
            if (!empty($customer->email) && str_contains($customer->email, '@')) {
                $domain = strtolower(explode('@', $customer->email)[1]);
                $businessEmail = !in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com']);
            }
            
            $emailType = $businessEmail ? "🏢" : "👤";
            $date = $customer->created_at->format('M d');
            
            $response .= "{$number}. {$customer->name} {$emailType} {$phone}\n";
            $response .= "   📧 {$customer->email}\n";
            $response .= "   📅 Joined: {$date}\n\n";
        }
        
        $response .= "🔍 Legend: 🏢=Business 👤=Personal 📱=Has Phone ❌=No Phone";
        
        return $response;
    }
    
    /**
     * Provide help menu with all available commands
     */
    private function getHelpMenu()
    {
        return "🤖 **CUSTOMER MANAGEMENT & BUSINESS INTELLIGENCE BOT**\n\n" .
               "📝 **CUSTOMER MANAGEMENT:**\n" .
               "• `add customer [Name] with email [email@example.com]` - Add new customer\n" .
               "• `list customers` - Show all customers\n" .
               "• `update customer [Name] email to [newemail@example.com]` - Update email\n" .
               "• `delete customer [Name]` - Remove customer\n\n" .
               "📊 **BUSINESS INTELLIGENCE:**\n" .
               "• `analyze customers` - Full customer analysis\n" .
               "• `marketing insights` - Marketing recommendations\n" .
               "• `business recommendations` - Strategic advice\n" .
               "• `quick stats` - Customer overview\n" .
               "• `top customers` - Recent customer list\n\n" .
               "💡 **Examples:**\n" .
               "• \"add customer John Doe with email john@example.com\"\n" .
               "• \"update customer John email to john@newdomain.com\"\n" .
               "• \"analyze customers\" - Get business insights\n\n" .
               "❓ Need help? Just type your command or use the quick action buttons above!";
    }
    
    /**
     * Call your existing LLaMA API (placeholder)
     */
    private function callLlamaAPI($message)
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
        
        
        // Temporary placeholder response
        //return "This is a placeholder response. Integrate your LLaMA API here. Your message was: " . $message;
    }
}