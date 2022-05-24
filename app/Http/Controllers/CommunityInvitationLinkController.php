<?php

namespace App\Http\Controllers;

use App\Models\CommunityInvitation;
use App\Models\CommunityInvitationLink;
use Illuminate\Http\Request;

class CommunityInvitationLinkController extends Controller
{

    public function getInvitationLinkInformations(Request $request)
    {
        if (!$request->code) {
            return response()->json([
                "error" => "INVALID_CODE",
            ], 400);
        }

        if (auth()->user()->community_id) {
            return response()->json([
                "error" => "ALREADY_IN_COMMUNITY",
            ], 403);
        }

        $invitationLink = CommunityInvitationLink::where('code', $request->code)->first();

        if (!$invitationLink) {
            return response()->json([
                "error" => "INVALID_INVITATION_CODE",
            ], 403);
        }

        if ($invitationLink->community_id == auth()->user()->community_id) {
            return response()->json([
                "error" => "ALREADY_IN_COMMUNITY",
            ], 403);
        }

        if ($invitationLink->nb_invitations >= $invitationLink->invitations_limit) {
            return response()->json([
                "error" => "INVITATION_LIMIT_REACHED",
            ], 403);
        }

        return response()->json([
            "community" => $invitationLink->community,
            "invitation_link" => $invitationLink,
        ]);
    }

    public function useInvitationLink(Request $request)
    {
        if (!$request->code) {
            return response()->json([
                "error" => "INVALID_CODE",
            ], 400);
        }

        if (auth()->user()->community_id) {
            return response()->json([
                "error" => "ALREADY_IN_COMMUNITY",
            ], 403);
        }

        $invitationLink = CommunityInvitationLink::where('code', $request->code)->first();

        if (!$invitationLink) {
            return response()->json([
                "error" => "INVALID_INVITATION_LINK",
            ], 403);
        }

        if ($invitationLink->community_id == auth()->user()->community_id) {
            return response()->json([
                "error" => "ALREADY_IN_COMMUNITY",
            ], 403);
        }

        if ($invitationLink->nb_invitations >= $invitationLink->invitations_limit) {
            return response()->json([
                "error" => "INVITATION_LIMIT_REACHED",
            ], 403);
        }

        $invitation = CommunityInvitation::create([
            'user_id' => auth()->user()->id,
            'community_id' => $invitationLink->community_id,
        ]);

        $invitationLink->nb_invitations++;
        $invitationLink->save();


        return response()->json([
            "success" => true,
            "invitation" => $invitation,
        ]);
    }

    public function getInvitationLink(Request $request)
    {

        if (auth()->user()->community_role != "owner" && auth()->user()->community_role != "admin" && auth()->user()->community_role != "moderator") {
            return response()->json([
                "error" => "USER_DOES_NOT_HAVE_PERMISSION",
            ], 403);
        }

        $invitationLink = CommunityInvitationLink::where('community_id', auth()->user()->community_id)->first();

        if (!$invitationLink) {
            return response()->json([
                "error" => "INVALID_INVITATION_LINK",
            ], 403);
        }

        if ($invitationLink->nb_invitations >= $invitationLink->invitations_limit) {
            return response()->json([
                "error" => "INVITATION_LIMIT_REACHED",
            ], 403);
        }

        return response()->json([
            "invitation_link" => $invitationLink
        ]);
    }
}
