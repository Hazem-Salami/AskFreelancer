<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\MediaProject;
use App\Models\PreviousProject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    use Traits\ImageTrait;
    use Traits\ResponseTrait;

    public function index()
    {
        return $this->success(
            'المشاريع',
            PreviousProject::where('user_id', auth()->user()->id)->get()
        );
    }

    public function userProjects($id)
    {
        return $this->success(
            'المشاريع',
            PreviousProject::where('user_id', $id)->get()
        );
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5|max:32',
            'description' => 'required|string|min:5',
            'link' => 'string|min:25',
            'cover' => 'max:5000|mimes:bmp,jpg,png,jpeg,svg',
            'media' => 'array',
            'media.*' => 'required|max:20000|mimes:bmp,jpg,png,jpeg,svg,gif,flv,mp4,mkv,m4v,gifv,m3u8,ts,3gp,mov,avi,wmv',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            if ($request->cover) {
                $cover = $request->file('cover');
                $image = $this->saveImage($cover, 'freelancers projects');

                $project = PreviousProject::create([
                    'user_id' => auth()->user()->id,
                    'name' => $request->get('name'),
                    'description' => $request->get('description'),
                    'link' => $request->get('link'),
                    'cover_image' => $image['path'],
                ]);
            } else
                $project = PreviousProject::create([
                    'user_id' => auth()->user()->id,
                    'name' => $request->get('name'),
                    'description' => $request->get('description'),
                    'link' => $request->get('link'),
                ]);

            if ($request->has('media')) {
                $media = $request->file('media');
                if ($media != null) {
                    $i = 0;
                    foreach ($media as $file) {

                        $i++;
                        $media = $this->saveImage($file, 'freelancers projects', $i);

                        $medias[] = MediaProject::create([
                            'path' => $media['path'],
                            'project_id' => $project->id,
                        ]);
                    }
                }
            }
            return $this->success('تم إنشاءالمشروع', $project);
        }
    }

    public function show($id)
    {
        $project = PreviousProject::find($id);
        if ($project === null)
            return $this->failed('There is no project with this ID');
        $project_media = MediaProject::where('project_id', $id)->get();

        $response = [
            'Prject details' => $project,
            'media' => $project_media
        ];
        return $this->success('Project ' . $id, $response);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->post(), [
            'name' => 'string|min:5|max:32',
            'description' => 'string|min:5',
            'link' => 'string|min:25',
            'media' => 'array',
            'media.*' => 'required|max:20000|mimes:bmp,jpg,png,jpeg,svg,gif,flv,mp4,mkv,m4v,gifv,m3u8,ts,3gp,mov,avi,wmv',
            'delete_media' => 'array',
            'delete_media.*' => 'required|integer|min:1|exists:media_projects,id',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $project = PreviousProject::find($id);
            if ($request->name)
                $project->name = $request->name;
            if ($request->description)
                $project->description = $request->description;
            if ($request->link)
                $project->link = $request->link;
            $project->save();

            if ($request->has('media')) {
                $media = $request->file('media');
                if ($media != null) {
                    $i = 0;
                    foreach ($media as $file) {

                        $i++;
                        $media = $this->saveImage($file, 'freelancers projects', $i);

                        $medias[] = MediaProject::create([
                            'path' => $media['path'],
                            'project_id' => $project->id,
                        ]);
                    }
                }
            }

            if ($request->has('delete_media')) {
                $delete_media = $request->get('delete_media');

                foreach ($delete_media as $media) {
                    $media_record = MediaProject::find($media);

                    if (File::exists(public_path($media_record->path)))
                        File::delete(public_path($media_record->path));
                    MediaProject::destroy($media);
                }
            }
            return $this->success('تم إنشاءالمشروع', $project);
        }
    }

    public function destroy($id)
    {
        $project = PreviousProject::find($id);
        if ($project === null)
            return $this->failed('There is no project with this ID');
        $project_media = MediaProject::where('project_id', $id)->get();

        foreach ($project_media as $media) {

            if (File::exists(public_path($media->path)))
                File::delete(public_path($media->path));
        }

        PreviousProject::destroy($id);
        return $this->success('Deleting Success!');
    }
}
