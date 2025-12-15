<?php

namespace App\Http\Controllers\admin;

use App\Helpers\SupabaseStorage;
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
        $data->fill(Arr::except($validated, ['image']));
        $data['title'] = Str::title($data['title']);
        $data['created_by'] = Auth::id();

        if($request->hasFile('image')){

            $file = $request->file('image');
            $tempName = 'event_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $tempPath = storage_path("app/temp/" . $tempName);

            // ensure temp directory exists
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }

            $file->move(dirname($tempPath), basename($tempPath));

            $publicUrl = SupabaseStorage::upload($tempPath, "events");

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
        $event->fill(Arr::except($validated, ['image']));
        $event['title'] = Str::title($validated['title']);


        if($request->hasFile('image')){

            $oldImage = $event->image; // keep old image

            $file = $request->file('image');
            $tempName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $tempPath = storage_path("app/temp/" . $tempName);

            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }

            $file->move(dirname($tempPath), basename($tempPath));

            $publicUrl = SupabaseStorage::upload($tempPath, "events");

            $event->image = $publicUrl;

            if ($oldImage) {
                SupabaseStorage::delete($oldImage);
            }

            unlink($tempPath);
        }

        $event->save();

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
            SupabaseStorage::delete($event->image);
        }

        log_admin_activity('deleted_event', "Deleted event: {$event->title}");

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
            'data' => new EventResource($event)
        ]);
    }
}
