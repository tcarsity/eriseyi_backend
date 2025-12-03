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

    public function show(Event $event)
    {
        return new EventResource($event);
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
            $path = public_path('uploads/events');


            // create folder if it doesnt exist
            if(!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $image = $request->file('image');
            $filename = 'event_' . Str::random(8) . '.' . $image->getClientOriginalExtension();

            // resize / compress using intervention
            $img = Image::read($image->getRealPath())
            ->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // save compressed version
            $img->save($path . '/' . $filename, 85);

            $data['image'] = 'uploads/events/' . $filename;

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
            //Delete old image if it exists
            if($event->image && file_exists(public_path($event->image))) {
                File::delete(public_path($event->image));
            }

            //create folder if not exists
            $path = public_path('uploads/events');
            if(!file_exists($path)){
                mkdir($path, 0777, true);
            }

            // process new image
            $image = $request->file('image');
            $filename = 'event_' . Str::random(8) . '.' . $image->getClientOriginalExtension();

            //Resize /compress using intervention
            $img = Image::read($image->getRealPath())
            ->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // save compressed version
            $img->save($path . '/' . $filename, 85);

            $data['image'] = 'uploads/events/' . $filename;
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
        $uplaodPath = public_path('uploads/events');

        //Delete event image from storage if it exist
        if($event->image && File::exists($uplaodPath . '/' . basename($event->image))) {
            File::delete($uplaodPath . '/' . basename($event->image));
        }

        log_admin_activity('deleted_event', "Deleted event: {$event->title}");

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
            'data' => new EventResource($event)
        ]);
    }
}
