<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\Category;
use App\Models\MediaPost;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    public function search($search)
    {
        $posts = Post::join('users', 'users.id', '=', 'posts.user_id')
            ->where('posts.body', 'like', "%{$search}%")
            ->orWhere('users.first_name', 'like', "%{$search}%")
            ->orWhere('users.last_name', 'like', "%{$search}%")
            ->orderBy('posts.created_at', 'desc')
            ->get();

        foreach ($posts as $post) {

            $post->user;

            $post->mediaposts;

            $postcategories = $post->postcategories;

            foreach ($postcategories as $postcategory) {
                $postcategory->category;
            }
        }
        return $this->success('المشاريع', $posts);
    }

    /*
     *
     * create post
     * Create a user post on the database
     * 0 : Non small services
     * 1 : small services
     * @return message by JsonResponse
     * */
    public function createPost(Request $request)
    {
        try {
            Validator::extend('date_multi_format', function ($attribute, $value, $formats) {
                foreach ($formats as $format) {
                    $parsed = date_parse_from_format($format, $value);
                    if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
                        return true;
                    }
                }
                return false;
            });

            $rules = [
                'title' => ['required', 'string'],
                'body' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'deliveryDate' => ['required', 'date', 'date_multi_format:"Y-n-j","Y-m-d"', 'after:now'],
                'media' => 'array',
                'media.*' => 'required|max:20000|mimes:bmp,jpg,png,jpeg,svg,gif,flv,mp4,mkv,m4v,gifv,m3u8,ts,3gp,mov,avi,wmv,pdf',
                'category' => ['required', 'array'],
                'category.*' => ['required', 'numeric', 'exists:categories,id'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $user = User::find(auth()->user()->id);

            if ($request->price <= 30) {
                $post = Post::create([
                    'title' => $request->title,
                    'body' => $request->body,
                    'user_id' => $user['id'],
                    'price' => $request->price,
                    'deliveryDate' => $request->deliveryDate,
                    'type' => 1,
                ]);
            } else {
                $post = Post::create([
                    'title' => $request->title,
                    'body' => $request->body,
                    'user_id' => $user['id'],
                    'price' => $request->price,
                    'deliveryDate' => $request->deliveryDate,
                    'type' => 2,
                ]);
            }

            if ($request->has('media')) {
                $media = $request->file('media');
                if ($media != null) {
                    $i = 0;
                    foreach ($media as $file) {

                        $i++;
                        $media = $this->saveImage($file, 'freelancers Posts', $i);

                        $medias[] = MediaPost::create([
                            'path' => $media['path'],
                            'post_id' => $post->id,
                        ]);
                    }
                }
            }

            foreach ($request->category as $ID) {
                PostCategory::create([
                    'post_id' => $post['id'],
                    'category_id' => $ID,
                ]);
            }

            $message = 'تم إنشاء منشور بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * edit post
     * Edit a user post on the database
     * @return message by JsonResponse
     * */
    public function editPost(Request $request, $id)
    {
        try {
            $post = Post::find($id);

            Validator::extend('date_multi_format', function ($attribute, $value, $formats) {
                foreach ($formats as $format) {
                    $parsed = date_parse_from_format($format, $value);
                    if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
                        return true;
                    }
                }
                return false;
            });

            $request['created_at'] = $post['created_at'];

            $rules = [
                'title' => ['string'],
                'body' => ['string'],
                'price' => ['numeric'],
                'created_at' => ['date'],
                'deliveryDate' => ['date', 'date_multi_format:"Y-n-j","Y-m-d"', 'after:created_at'],
                'media' => 'array',
                'media.*' => 'required|max:20000|mimes:bmp,jpg,png,jpeg,svg,gif,flv,mp4,mkv,m4v,gifv,m3u8,ts,3gp,mov,avi,wmv,pdf',
                'delete_media' => 'array',
                'delete_media.*' => 'required|integer|min:1|exists:media_posts,id',
                'category' => ['array'],
                'category.*' => ['required', 'numeric', 'exists:categories,id'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            if ($request->title)
                $post->title = $request->title;

            if ($request->body)
                $post->body = $request->body;

            if ($request->price) {
                $post->price = $request->price;

                if ($request->price <= 30)
                    $post->type = 1;
                else {
                    $post->type = 0;
                }
            }

            if ($request->deliveryDate)
                $post->deliveryDate = $request->deliveryDate;

            $post->save();

            if ($request->category) {

                $postcategories = $post->postcategories;

                foreach ($postcategories as $postcategory) {
                    $postcategory->delete();
                }

                foreach ($request->category as $ID) {
                    PostCategory::create([
                        'post_id' => $post['id'],
                        'category_id' => $ID,
                    ]);
                }
            }

            if ($request->has('media')) {
                $media = $request->file('media');
                if ($media != null) {
                    $i = 0;
                    foreach ($media as $file) {

                        $i++;
                        $media = $this->saveImage($file, 'freelancers posts', $i);

                        $medias[] = MediaPost::create([
                            'path' => $media['path'],
                            'post_id' => $post->id,
                        ]);
                    }
                }
            }

            if ($request->has('delete_media')) {
                $delete_media = $request->get('delete_media');

                foreach ($delete_media as $media) {
                    $media_record = MediaPost::find($media);

                    if (File::exists(public_path($media_record->path)))
                        File::delete(public_path($media_record->path));
                    $media_record->delete();
                }
            }

            $message = 'تم تعديل المنشور بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * delete post
     * Delete a user post on the database
     * @return message by JsonResponse
     * */
    public function deletePost($id)
    {
        try {
            $post = Post::find($id);

            $mediaposts = $post->mediaposts;

            foreach ($mediaposts as $mediapost) {
                if (File::exists(public_path($mediapost->path)))
                    File::delete(public_path($mediapost->path));
                $mediapost->delete();
            }

            $offers = $post->offers;

            foreach ($offers as $offer) {
                $offer->delete();
            }

            $postcategories = $post->postcategories;

            foreach ($postcategories as $postcategory) {
                $postcategory->delete();
            }

            $post->delete();
            $message = 'تم حذف المنشور بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get Post
     * Get the post by post id
     * @return Data by JsonResponse : <All about the post>
     * */
    public function getPost($id)
    {
        try {
            $post = Post::find($id);

            $post->mediaposts;

            $post->offers;

            $postcategories = $post->postcategories;

            $post->user;

            foreach ($postcategories as $postcategory) {
                $postcategory->category;
            }

            return $this->success('post ' . $id, $post);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get User Posts
     * Get all id user posts
     * @return Data by JsonResponse : array of posts
     * */
    public function getUserPosts($id)
    {
        try {

            $user = User::find($id);

            $posts = $user->posts;

            foreach ($posts as $post) {
                $post->mediaposts;

                $post->user;

                $post->offers;

                $postcategories = $post->postcategories;

                foreach ($postcategories as $postcategory) {
                    $postcategory->category;
                }
            }

            return $this->success('user ' . $id, $posts);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get small services
     * Get all small services
     * @return Data by JsonResponse : array of posts
     * */
    public function getSmallServices()
    {
        try {

            $posts = Post::where('type', 1)->orderBy('created_at', 'desc')->get();

            foreach ($posts as $post) {

                $post->user;

                $post->mediaposts;

                $post->offers;

                $postcategories = $post->postcategories;

                foreach ($postcategories as $postcategory) {
                    $postcategory->category;
                }
            }

            return $this->success('small services ', $posts);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
    *
    * get non small services
    * Get all non small services
    * @return Data by JsonResponse : array of posts
    * */
    public function getNonSmallServices()
    {
        try {

            $posts = Post::where('type', 2)->orderBy('created_at', 'desc')->get();

            foreach ($posts as $post) {

                $post->user;

                $post->mediaposts;

                $post->offers;

                $postcategories = $post->postcategories;

                foreach ($postcategories as $postcategory) {
                    $postcategory->category;
                }
            }

            return $this->success('non small services ', $posts);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
