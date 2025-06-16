<?php

namespace App\Http\Controllers\Api\Documentation;

/**
 * @OA\Schema(
 *     schema="Account",
 *     type="object",
 *     title="Account",
 *     required={"uuid", "user_uuid", "name", "balance", "frozen"},
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="user_uuid", type="string", format="uuid", example="660e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Savings Account"),
 *     @OA\Property(property="balance", type="integer", example=50000, description="Balance in cents"),
 *     @OA\Property(property="frozen", type="boolean", example=false, description="Whether the account is frozen"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     required={"uuid", "account_uuid", "type", "amount", "balance_after", "description", "hash"},
 *     @OA\Property(property="uuid", type="string", format="uuid", example="770e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="account_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="type", type="string", enum={"deposit", "withdrawal"}, example="deposit"),
 *     @OA\Property(property="amount", type="integer", example=10000, description="Amount in cents"),
 *     @OA\Property(property="balance_after", type="integer", example=60000, description="Balance after transaction in cents"),
 *     @OA\Property(property="description", type="string", example="Monthly salary deposit"),
 *     @OA\Property(property="hash", type="string", example="3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e", description="SHA3-512 transaction hash"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Transfer",
 *     type="object",
 *     title="Transfer",
 *     required={"uuid", "from_account_uuid", "to_account_uuid", "amount", "description", "status", "hash"},
 *     @OA\Property(property="uuid", type="string", format="uuid", example="880e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="from_account_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="to_account_uuid", type="string", format="uuid", example="660e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="amount", type="integer", example=5000, description="Amount in cents"),
 *     @OA\Property(property="description", type="string", example="Payment for services"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}, example="completed"),
 *     @OA\Property(property="hash", type="string", example="4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f", description="SHA3-512 transfer hash"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-01T00:00:01Z", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="Balance",
 *     type="object",
 *     title="Balance",
 *     required={"account_uuid", "balance", "frozen"},
 *     @OA\Property(property="account_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="balance", type="integer", example=50000, description="Current balance in cents"),
 *     @OA\Property(property="frozen", type="boolean", example=false),
 *     @OA\Property(property="last_updated", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(
 *         property="turnover",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="total_debit", type="integer", example=100000),
 *         @OA\Property(property="total_credit", type="integer", example=150000),
 *         @OA\Property(property="month", type="integer", example=1),
 *         @OA\Property(property="year", type="integer", example=2024)
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Asset",
 *     type="object",
 *     title="Asset",
 *     required={"code", "name", "type", "precision", "is_active"},
 *     @OA\Property(property="code", type="string", example="USD", description="Asset code (e.g., USD, EUR, BTC)"),
 *     @OA\Property(property="name", type="string", example="US Dollar"),
 *     @OA\Property(property="type", type="string", enum={"fiat", "crypto", "commodity", "custom"}, example="fiat"),
 *     @OA\Property(property="precision", type="integer", example=2, description="Number of decimal places"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="metadata", type="object", nullable=true, description="Additional asset metadata"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="ExchangeRate",
 *     type="object",
 *     title="Exchange Rate",
 *     required={"from_asset_code", "to_asset_code", "rate", "is_active"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="from_asset_code", type="string", example="USD"),
 *     @OA\Property(property="to_asset_code", type="string", example="EUR"),
 *     @OA\Property(property="rate", type="string", example="0.8500000000", description="Exchange rate with 10 decimal precision"),
 *     @OA\Property(property="bid", type="string", nullable=true, example="0.8495000000"),
 *     @OA\Property(property="ask", type="string", nullable=true, example="0.8505000000"),
 *     @OA\Property(property="source", type="string", example="manual", description="Rate source"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="valid_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="AccountBalance",
 *     type="object",
 *     title="Account Balance",
 *     required={"account_uuid", "asset_code", "balance"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="account_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="asset_code", type="string", example="USD"),
 *     @OA\Property(property="balance", type="integer", example=50000, description="Balance in smallest unit (cents for USD)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="asset", ref="#/components/schemas/Asset"),
 *     @OA\Property(property="account", ref="#/components/schemas/Account")
 * )
 * 
 * @OA\Schema(
 *     schema="Poll",
 *     type="object",
 *     title="Poll",
 *     required={"id", "title", "type", "status", "options", "start_date", "end_date"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Should we add support for Japanese Yen?"),
 *     @OA\Property(property="description", type="string", nullable=true, example="This poll determines whether to add JPY support to the platform"),
 *     @OA\Property(property="type", type="string", enum={"single_choice", "multiple_choice", "weighted_choice", "yes_no", "ranked_choice"}, example="yes_no"),
 *     @OA\Property(property="status", type="string", enum={"draft", "active", "completed", "cancelled"}, example="active"),
 *     @OA\Property(
 *         property="options",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", example="yes"),
 *             @OA\Property(property="label", type="string", example="Yes, add JPY support")
 *         )
 *     ),
 *     @OA\Property(property="voting_power_strategy", type="string", example="OneUserOneVoteStrategy"),
 *     @OA\Property(property="execution_workflow", type="string", nullable=true, example="AddAssetWorkflow"),
 *     @OA\Property(property="min_participation", type="integer", nullable=true, example=100),
 *     @OA\Property(property="winning_threshold", type="number", format="float", nullable=true, example=0.5),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", example="2024-01-08T00:00:00Z"),
 *     @OA\Property(property="created_by", type="string", format="uuid", example="660e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="votes_count", type="integer", example=150, description="Total number of votes"),
 *     @OA\Property(property="total_voting_power", type="integer", example=500, description="Total voting power cast")
 * )
 * 
 * @OA\Schema(
 *     schema="Vote",
 *     type="object",
 *     title="Vote",
 *     required={"id", "poll_id", "user_uuid", "selected_options", "voting_power", "voted_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="poll_id", type="integer", example=1),
 *     @OA\Property(property="user_uuid", type="string", format="uuid", example="660e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(
 *         property="selected_options",
 *         type="array",
 *         @OA\Items(type="string"),
 *         example={"yes"}
 *     ),
 *     @OA\Property(property="voting_power", type="integer", example=10),
 *     @OA\Property(property="signature", type="string", nullable=true, example="abc123def456"),
 *     @OA\Property(property="voted_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="poll", ref="#/components/schemas/Poll")
 * )
 * 
 * @OA\Schema(
 *     schema="PollResult",
 *     type="object",
 *     title="Poll Result",
 *     required={"poll", "results", "participation"},
 *     @OA\Property(property="poll", ref="#/components/schemas/Poll"),
 *     @OA\Property(
 *         property="results",
 *         type="object",
 *         description="Vote results by option",
 *         example={
 *             "yes": {"votes": 75, "voting_power": 250},
 *             "no": {"votes": 25, "voting_power": 100}
 *         }
 *     ),
 *     @OA\Property(
 *         property="participation",
 *         type="object",
 *         @OA\Property(property="total_votes", type="integer", example=100),
 *         @OA\Property(property="total_voting_power", type="integer", example=350),
 *         @OA\Property(property="participation_rate", type="number", format="float", example=0.25),
 *         @OA\Property(property="winning_option", type="string", nullable=true, example="yes"),
 *         @OA\Property(property="meets_threshold", type="boolean", example=true)
 *     ),
 *     @OA\Property(property="calculated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="error", type="string", example="VALIDATION_ERROR", nullable=true),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         nullable=true,
 *         additionalProperties={"type":"array", "items":{"type":"string"}}
 *     )
 * )
 */
class Schemas
{
}