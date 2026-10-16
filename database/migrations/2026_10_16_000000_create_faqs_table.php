<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        $now = now();
        $starter = [
            ['General', 'How do I enquire about a car?', "Open the car's page and tap Chat on WhatsApp or Call. Mention the listing title so we know which car you mean."],
            ['General', 'Where can I see the cars?', 'Visit us at our showroom - the address, map and opening hours are on the Contact page. We recommend messaging first so the car is ready for you.'],
            ['Buying', 'What does "down payment" on a listing mean?', 'It is the amount you pay up-front when buying the car. The rest can be paid in cash or financed through a bank or leasing company.'],
            ['Buying', 'Is the price negotiable?', 'Prices and discounts are shown openly on each listing. Message us to discuss the car and we will be happy to talk it through.'],
            ['Finance & leasing', 'Can I lease or finance a car?', 'Many banks and leasing companies finance vehicle purchases. Use our Finance Calculator to estimate the monthly payment, then confirm the rate and terms with your lender.'],
            ['Finance & leasing', 'How much down payment do lenders require?', 'Since 25 May 2026 the Central Bank of Sri Lanka limits how much of a vehicle\'s value can be financed - as low as 40% for new or recently registered cars and up to 60% for vehicles registered over a year. That means a larger down payment than before. Always confirm the current rules with your lender.'],
            ['Selling', 'How do I sell my car through Halcyon Auto?', 'Get in touch through the Contact page or WhatsApp and tell us about your car. We will let you know the next steps.'],
            ['Selling', 'Do you buy cars or only list them?', 'Message us with your car\'s details and we will explain the options available.'],
        ];

        foreach ($starter as $i => [$category, $question, $answer]) {
            DB::table('faqs')->insert([
                'category' => $category,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $i + 1,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
