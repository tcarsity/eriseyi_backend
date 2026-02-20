<?php



namespace App\Http\Resources;



use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;

use Carbon\Carbon;



class UserResource extends JsonResource

{

    /**

     * Transform the resource into an array.

     *

     * @return array<string, mixed>

     */

    public function toArray(Request $request): array

    {

        return [

            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->when(

                auth()->check() && auth()->user()->role === 'superadmin',

                $this->email

            ),

            'role' => $this->role,

            'status' => $this->status,



            // 🔥 Important change here

            'invite_display_status' => $this->getDisplayStatus(),



            'invite_status' => $this->invite_status, // optional (can remove later)

            'invite_sent_at' => $this->invite_sent_at,



            'last_seen' => $this->last_seen,

            'is_active' => $this->last_seen

                ? Carbon::parse($this->last_seen)->gt(now()->subSeconds(60))

                : false,

        ];

    }

    /**

     * Compute invite display status

     */

    private function getDisplayStatus()
    {

            // If account is permanently activated

                if ($this->invite_status === 'active') {

                return 'active';

            }



            if (

                $this->invite_status === 'pending' &&

                $this->invite_sent_at &&

                $this->invite_sent_at->gt(now()->subHours(24))

            ) {

                return 'pending';

            }



            return 'resend';

    }

        protected $casts = [

            'invite_sent_at' => 'datetime',

        ];



    public function with($request)
    {

        return [

            'status' => 'success',

        ];

    }

}
