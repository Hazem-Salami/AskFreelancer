<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\Service;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    public function index()
    {
        return $this->success('خدماتنا', Service::all());
    }

    public function index_cms()
    {
        return $this->success('Serivces', Service::paginate(10));
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:15',
            'body' => 'required|string|min:5|max:4000',
            'cover' => 'max:5000|mimes:bmp,jpg,png,jpeg,svg',

        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            if ($request->cover) {
                $cover = $request->file('cover');
                $image = $this->saveImage($cover, 'serivces');

                $service = Service::create([
                    'title' => $request->title,
                    'body' => $request->body,
                    'image' => $image['path'],
                ]);
            } else
                $service = Service::create([
                    'title' => $request->title,
                    'body' => $request->title,
                ]);

            $message = 'Creating Success';
            return $this->success($message, $service);
        }
    }

    public function show($id)
    {
        $service = Service::find($id);
        if ($service === null)
            return $this->failed("There is no service with this ID");

        return $this->success('Service ' . $id, $service);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:15',
            'body' => 'required|string|min:5|max:4000',
            'cover' => 'max:5000|mimes:bmp,jpg,png,jpeg,svg',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            $service = Service::find($id);
            if ($service === null)
                return $this->failed("There is no service with this ID");

            $service->title = $request->title;
            $service->body = $request->body;
            if ($request->cover) {
                $cover = $request->file('cover');
                $image = $this->saveImage($cover, 'serivces');

                if (File::exists(public_path($service->image)))
                    File::delete(public_path($service->image));
                $service->image = $image['path'];
            }
            $service->save();

            $message = 'Updating Success';
            return $this->success($message, $service);
        }
    }

    public function destroy($id)
    {
        $service = Service::find($id);
        if ($service === null)
            return $this->failed("There is no service with this id");

        if (File::exists(public_path($service->image)))
            File::delete(public_path($service->image));

        Service::destroy($id);
        return $this->failed('Deleted Success!');
    }
}
