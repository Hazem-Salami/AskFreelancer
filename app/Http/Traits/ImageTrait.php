<?php

namespace App\Http\Traits;

use Carbon\Carbon;

trait ImageTrait
{
    public function saveImage($file, $string, $counter = null)
    {
        $extension = $file->getClientOriginalExtension();
        $type = $this->getMediaType($extension);
        $time = Carbon::now();
        $time = $time->toDateString() . '_' . $time->hour . '_' . $time->minute . '_' . $time->second;

        if ($counter != null) {

            $name = $time . '_' . $string . '_' . $type['directory'] . $counter . '.' . $extension;
            $file->move($string . '/' . $type['folder'], $name);
            $path = '/' . $string . '/' . $type['folder'] . '/' . $name;
        } else {

            $name = $time . '_' . $string . '_' . $type['directory'] . '.' . $extension;
            $file->move($string . '/' . $type['folder'], $name);
            $path = '/' . $string . '/' . $type['folder'] . '/' . $name;
        }
        return [
            'name' => $name,
            'path' => $path,
            'type' => $type['type']
        ];
    }

    private function getMediaType($extension)
    {
        if ($this->is_image($extension)) {

            $directory = 'image_';
            $folder = 'images';
            $type = 1;
        } elseif ($this->is_gif($extension)) {

            $directory = 'gif_';
            $folder = 'gifs';
            $type = 2;
        } elseif ($this->is_document($extension)) {

            $directory = 'document_';
            $folder = 'documents';
            $type = 3;
        } else {

            $directory = 'video_';
            $folder = 'videos';
            $type = 0;
        }
        $response = [
            'directory' => $directory,
            'folder' => $folder,
            'type' => $type,
        ];
        return $response;
    }

    private function is_image($extension)
    {
        if (
            $extension == 'jpg' || $extension == 'jpeg'
            || $extension == 'png' || $extension == 'svg' ||
            $extension == 'bmp'
        )
            return true;
        return false;
    }

    private function is_gif($extension)
    {
        if ($extension == 'gif')
            return true;
        return false;
    }

    private function is_document($extension)
    {
        if ($extension == 'pdf' || $extension == 'docx')
            return true;
        return false;
    }
}
