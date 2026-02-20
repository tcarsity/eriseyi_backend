<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Http\Resources\MemberResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Rules\NigerianPhoneUnique;

class MemberController extends Controller
{

    // Display all members
    public function index()
    {
        $members = Member::latest()->paginate(10);
        return MemberResource::collection($members);

    }

    public function store(Request $request)
    {
            $validator = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => ['required','regex:/^(70|71|80|81|90|91)\d{8}$/', new NigerianPhoneUnique()],
                'address' => 'required|string|max:500',
                'gender' => 'required|in:male,female',
                'birth_month' => 'required|string',
                'birth_date' => 'required|integer'
            ]);


            $member = Member::create([
                ...$validator,
                'created_by' => null,
            ]);

            return (new MemberResource($member))
            ->additional(['message' => 'Member added successfully']);

    }


    public function publicStore(Request $request)
    {
            $validator = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => ['required','regex:/^(70|71|80|81|90|91)\d{8}$/', new NigerianPhoneUnique()],
                'address' => 'required|string|max:500',
                'gender' => 'required|in:male,female',
                'birth_month' => 'required|string',
                'birth_date' => 'required|integer'
            ]);


            $member = Member::create([
                ...$validator,
                'created_by' => null,
            ]);

            return (new MemberResource($member))
            ->additional(['message' => 'Member added successfully']);

    }

    public function adminStore(Request $request)
    {
            $validator = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => ['required','regex:/^(70|71|80|81|90|91)\d{8}$/', new NigerianPhoneUnique()],
                'address' => 'required|string|max:500',
                'gender' => 'required|in:male,female',
                'birth_month' => 'required|string',
                'birth_date' => 'required|integer'
            ]);


            // Add created_by



            $member = Member::create([
                ...$validator,
                'created_by' => auth()->id(),
            ]);

            log_admin_activity('created_member', "Added member: {$member->name}");

            return (new MemberResource($member))
            ->additional(['message' => 'Member added successfully']);

    }


    public function show(Member $member)
    {

        return new MemberResource($member);
    }


    public function update(Request $request, Member $member)
    {
        $validator = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => ['sometimes', 'regex:/^[789][0-9]{9}$/', new NigerianPhoneUnique($member->id)],
            'address' => 'sometimes|string|max:500',
            'gender' => 'sometimes|in:male,female',
            'birth_month' => 'sometimes|string',
            'birth_date' => 'sometimes|integer'
        ]);

        try{
            $member->update($validator);

            log_admin_activity('updated_member', "Updated member: {$member->name}");

            return (new MemberResource($member))
                ->additional(['message' => 'Member updated successfully']);

        }catch (\Ecxeption $e){
            return new ErrorResource('Failed to update member');
        }
    }

    public function destroy(Member $member)
    {
        log_admin_activity('deleted_member',"Deleted member: {$member->name}");

        $member->delete();

        return new SuccessResource([
            'message' => 'Member deleted successfully',
        ]);
    }

    public function searchMember(Request $request)
    {

        $query = Member::query();

        if($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

            $q->where('name', 'ILIKE', "%{$search}%")

            ->orWhere('birth_month', 'ILIKE', "%{$search}%")

            ->orWhereRaw('LOWER(gender) = ?', [strtolower($search)]);

        });

    }

        $members = $query->latest()->paginate(10);

        return MemberResource::collection($members);

    }

    public function recentPublicMembers()
    {
        $today = now()->toDateString();

        $recentMembers = Member::whereNull('created_by')
            ->whereDate('created_at', $today)
            ->latest()
            ->get(['id', 'name', 'created_at']);

        return MemberResource::collection($recentMembers);
    }
}

