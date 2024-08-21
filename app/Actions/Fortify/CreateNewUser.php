<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Values\UserRoles;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param array<string, string> $input
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'is_business_customer' => ['required', 'boolean'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(
                User::create([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => Hash::make($input['password']),
                ]),
                function (User $user) use ($input) {
                    $this->createTeam($user);
                    if ($input['is_business_customer']) {
                        $user->assignRole(UserRoles::BUSINESS);
                        $this->createEventTables($user->uuid);
                    } else {
                        $user->assignRole(UserRoles::PRIVATE);
                    }
                }
            );
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }

    protected function createEventTables(string $uuid): void
    {
        Schema::create("accounts_{$uuid}", function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('name');
            $table->integer('user_uuid');
            $table->integer('balance')->default(0);
            $table->timestamps();

            $table->foreign('user_uuid')->references('uuid')->on('users');
        });

        Schema::create("snapshots_{$uuid}", function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('aggregate_uuid');
            $table->unsignedInteger('aggregate_version');
            $table->jsonb('state');

            $table->timestamps();

            $table->index('aggregate_uuid');
        });

        Schema::create("transactions_{$uuid}", function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('aggregate_uuid')->nullable();
            $table->unsignedBigInteger('aggregate_version')->nullable();
            $table->integer('event_version')->default(1);
            $table->string('event_class');
            $table->jsonb('event_properties');
            $table->jsonb('meta_data');
            $table->timestamp('created_at');
            $table->index('event_class');
            $table->index('aggregate_uuid');

            $table->unique(['aggregate_uuid', 'aggregate_version']);
        });
    }
}
