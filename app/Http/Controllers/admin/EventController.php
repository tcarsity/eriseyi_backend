<?php

namespace App\Http\Controllers\admin;

use App\Models\Event;
use App\Http\Resources\EventResource;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    //
    public function index()
    {
        $events = Event::latest('event_date')->get();
        return EventResource::collection($events);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'event_time' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // prepare base data (excluding image for now)
        $data = Arr::except($validated, ['image']);
        $data['title'] = Str::title($data['title']);
        $data['created_by'] = Auth::id();

        if($request->hasFile('image')){

            $image = $request->file('image');
            $tempName = 'event_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
            $tempPath = storage_path("app/temp/" . $tempName);

            // ensure temp directory exists
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }

            // resize / compress using intervention
            $img = Image::read($image->getRealPath())
            ->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })

            // save compressed version
            ->save($tempPath, 85);

            $publicUrl = SupabaseStorage::upload(new \Illuminate\Http\File($tempPath), "events");

            $data['image'] = $publicUrl;

            unlink($tempPath);

        }
        // create new event record
        $event = Event::create($data);

        log_admin_activity('created_event', "Added event: {$event->title}");

        return (new EventResource($event))
        ->additional([
            'message' => 'Event created successfully!',
        ])
        ->response()
        ->setStatusCode(201);
    }


    public function show(Event $event)
    {
        return new EventResource($event);
    }


    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'event_date' => 'sometimes|date',
            'event_time' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);


        // prepare base data (excluding image for now)
        $data = Arr::except($validated, ['image']);
        $data['title'] = Str::title($validated['title']);


        if($request->hasFile('image')){
            // delete old image from supabase
            // (optional — depends if you want deletion)
            // Supabase does not auto-delete old files

            // temp file for intervention
            $image = $request->file('image');
            $tempName = 'event_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
            $tempPath = storage_path("app/temp/" . $tempName);

            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }


            //Resize /compress using intervention
            $img = Image::read($image->getRealPath())
            ->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save($tempPath, 85);

            $publicUrl = SupabaseStorage::upload(new \Illuminate\Http\File($tempPath), "events");

            $data['image'] = $publicUrl;

            unlink($tempPath);
        }

        $event->update($data);

        log_admin_activity('updated_event', "Updated event: {$event->title}");

        return (new EventResource($event))
        ->additional([
            'message' => 'Event updated successfully!',
        ])
        ->response()
        ->setStatusCode(201);
    }

    public function destroy(Event $event)
    {
        if($event->image) {
        // Convert full public URL → relative path inside bucket
        $relativePath = str_replace(
            env('SUPABASE_URL').'/storage/v1/object/public/'.env('SUPABASE_BUCKET').'/',
            '',
            $event->image
        );

        // Delete from Supabase
        Http::withHeaders([
            'apikey' => env('SUPABASE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
        ])->delete(env('SUPABASE_URL')."/storage/v1/object/".env('SUPABASE_BUCKET')."/".$relativePath);
    }

        log_admin_activity('deleted_event', "Deleted event: {$event->title}");

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
            'data' => new EventResource($event)
        ]);
    }
}
