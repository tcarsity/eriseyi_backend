<?php

namespace App\Http\Controllers\admin;

use App\Models\Testimonial;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TestimonialResource;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $testimonials = Testimonial::latest()->take(4)->get();

        return TestimonialResource::collection($testimonials);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = $request->validate([
            'author' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        //  Count how many testimonials already exist

        $testimonialCount = Testimonial::count();

        if($testimonialCount >= 4){
            // Delete the oldest testimonial
            $oldest = Testimonial::oldest()->first();

            if($oldest->image && file_exists(public_path('uploads/testimonials/' . $oldest->image))){
                unlink(public_path('uploads/testimonials/' . $oldest->image));
            }

            $oldest->delete();
        }


       $testimonial = new Testimonial();
       $testimonial->fill(Arr::except($validator, ['image']));

       if($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::uuid() . '.' .$file->getClientOriginalExtension();

            Image::read($file)
                ->resize(300, 300)
                ->save(public_path('uploads/testimonials/' . $fileName));

            $testimonial->image = $fileName;
       }

       $testimonial->save();

       log_admin_activity('created_testimonial', "Added testimonial: {$testimonial->author}");

        return new TestimonialResource($testimonial);
    }


    public function show(Testimonial $testimonial)
    {
        return new TestimonialResource($testimonial);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        //
        $validator = $request->validate([
            'author' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $testimonial->fill(Arr::except($validator, ['image']));

        if($request->hasFile('image')){
            // delete old image if exists
            if($testimonial->image && File::exists(public_path('uplaods/testimonials/'. $testimonial->image))){
                File::delete(public_path('uploads/testimonials/' . $testimonial->image));
            }

            $file = $request->file('image');
            $fileName = Str::uuid() . '.' .$file->getClientOriginalExtension();

            Image::read($file)
                ->resize(300,300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save(public_path('uploads/testimonials/' . $fileName));

                $testimonial->image =  $fileName;
        }

            $testimonial->save();

            log_admin_activity('updated_testimonial', "Updated testimonial: {$testimonial->author}");

            return new TestimonialResource($testimonial);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Testimonial $testimonial)
    {
        $imagePath = public_path('uploads/testimonials/' .basename($testimonial->image));

        if($testimonial->image && file_exists($imagePath)){
            unlink($imagePath);
        }

        log_admin_activity('deleted_testimonial', "Deleted testimonial: {$testimonial->author}");

        $testimonial->delete();
        return response()->json(['message' => 'Testimonial deleted successfully']);
    }
}
