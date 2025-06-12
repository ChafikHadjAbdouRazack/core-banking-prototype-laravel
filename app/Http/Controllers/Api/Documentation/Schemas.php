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