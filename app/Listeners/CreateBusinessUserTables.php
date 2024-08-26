<?php

namespace App\Listeners;

use App\Events\BusinessUserCreated;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateBusinessUserTables
{
    /**
     * Handle the event.
     *
     * @param BusinessUserCreated $event
     * @return void
     */
    public function handle(BusinessUserCreated $event)
    {
        $uuid = $event->user->uuid;

        if (!Schema::hasTable("accounts_{$uuid}")) {
            Schema::create("accounts_{$uuid}", function (Blueprint $table) {
                $table->increments('id');
                $table->uuid();
                $table->string('name');
                $table->uuid('user_uuid');
                $table->integer('balance')->default(0);
                $table->timestamps();

                $table->foreign('user_uuid', 'accounts_user')->references('uuid')->on('users');
            });
        }

        if (!Schema::hasTable("snapshots_{$uuid}")) {
            Schema::create("snapshots_{$uuid}", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('aggregate_uuid');
                $table->unsignedInteger('aggregate_version');
                $table->jsonb('state');
                $table->timestamps();
                $table->index('aggregate_uuid', 'aggregate_uuid');
            });
        }

        if (!Schema::hasTable("transactions_{$uuid}")) {
            Schema::create("transactions_{$uuid}", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('aggregate_uuid')->nullable();
                $table->unsignedBigInteger('aggregate_version')->nullable();
                $table->integer('event_version')->default(1);
                $table->string('event_class');
                $table->jsonb('event_properties');
                $table->jsonb('meta_data');
                $table->timestamp('created_at');
                $table->index('event_class', 'event_class');
                $table->index('aggregate_uuid', 'aggregate_uuid');
                $table->unique(['aggregate_uuid', 'aggregate_version'], 'aggregate_uuid_version');
            });
        }
    }
}
