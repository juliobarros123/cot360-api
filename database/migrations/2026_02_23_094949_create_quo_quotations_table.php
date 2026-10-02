    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('quo_quotations', function (Blueprint $table) {
                $table->id();
                $table->string('quotation_number', 50)->unique()->nullable();
                $table->foreignId('customer_id')->constrained('ent_entities')->restrictOnDelete();
                $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->restrictOnDelete();
                $table->unsignedBigInteger('agent_id')->nullable()->comment('Agente/corretor');
                $table->string('opportunity_id', 50)->nullable();
                $table->string('lead_source', 50)->nullable();
                $table->enum('status', ['Draft', 'Pending', 'Sent', 'Accepted', 'Rejected', 'Expired', 'Converted'])->default('Draft');
                $table->string('slug', 100)->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('agent_id')->references('id')->on('ent_entities')->nullOnDelete();
                $table->index('quotation_number');
                $table->index('customer_id');
                $table->index('insurance_branch_id');
                $table->index('agent_id');
                $table->index('status');
                $table->index('opportunity_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('quo_quotations');
        }
    };
