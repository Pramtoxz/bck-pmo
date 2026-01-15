<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query();

        if ($request->has('type')) {
            $query->where('badge', 'LIKE', "%{$request->type}%");
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->get();

        return ApiResponse::success($campaigns->map(function($campaign) {
            return [
                'id' => (string) $campaign->id,
                'title' => $campaign->title,
                'badge' => $campaign->badge,
                'description' => $campaign->description,
                'image' => $campaign->image,
                'startDate' => $campaign->start_date->format('Y-m-d'),
                'endDate' => $campaign->end_date->format('Y-m-d'),
                'status' => $campaign->status,
            ];
        }));
    }

    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);

        return ApiResponse::success([
            'id' => (string) $campaign->id,
            'title' => $campaign->title,
            'badge' => $campaign->badge,
            'description' => $campaign->description,
            'image' => $campaign->image ,
            'startDate' => $campaign->start_date->format('Y-m-d'),
            'endDate' => $campaign->end_date->format('Y-m-d'),
            'status' => $campaign->status,
            'fullDescription' => $campaign->full_description,
            'partsIncluded' => $campaign->parts_included ?? [],
            'termsAndConditions' => $campaign->terms_and_conditions,
            'rewards' => $campaign->rewards ?? [],
        ]);
    }

    public function myAchievement(Request $request)
    {
        $campaign = Campaign::where('status', 'active')->first();

        if (!$campaign) {
            return ApiResponse::success(['currentCampaign' => null]);
        }

        return ApiResponse::success([
            'currentCampaign' => [
                'id' => (string) $campaign->id,
                'title' => $campaign->title,
                'endDate' => $campaign->end_date->format('Y-m-d H:i:s'),
                'achievementPercentage' => 0,
                'achievementLabel' => '0%',
            ]
        ]);
    }
}
