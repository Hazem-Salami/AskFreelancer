<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ResponseTrait;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ResponseTrait;

    public function getExceptCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'except' => 'array',
            'except.*' => 'required|integer|min:1|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            if ($request->has('except')) {
                $except = $request->get('except');
                return $this->success('My Permissions',
                    Category::whereNotIn('id', $except)->get());
            } else {
                return $this->success('My Permissions', Category::all());
            }
        }
    }

    public function index()
    {
        return $this->success('الفئات', Category::paginate(10));
    }

    public function getParent()
    {
        $parents = Category::where('parent_id', null)->get();
        return $this->success('الفئات الآباء', $parents);
    }

    public function getChildren()
    {
        $parents = Category::whereNot('parent_id', null)->get();
        return $this->success('الفئات الآباء', $parents);
    }

    public function getChild($id)
    {
        $children = Category::where('parent_id', $id)->get();
        return $this->success('الفئات الأبناء', $children);
    }

    public function create(Request $request)
    {

        $validator = Validator::make($request->post(), [
            'name' => 'required|string|min:3|max:15',
            'parent_id' => 'integer|min:1|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            if ($request->has('parent_id')) {
                $parent = Category::find($request->get('parent_id'));

                if ($parent === null) {
                    return $this->failed('لا يوجد فئة أب بهذا الرقم');
                } else {
                    $category = Category::create([
                        'name' => $request->get('name'),
                        'parent_id' => $request->get('parent_id'),
                    ]);
                }
            } else {
                $category = Category::create([
                    'name' => $request->get('name'),
                    'parent_id' => null
                ]);
            }
            return $this->success('تمت العملية بنجاح', $category);
        }
    }

    public function show($id)
    {
        $category = Category::find($id);
        if ($category === null)
            return $this->failed('لا يوجد فئة بهذا الرقم');
        return $this->success('الفئة ' . $id, $category);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|min:2|max:15',
            'parent_id' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            $parent_id = $request->get('parent_id');
            $category = Category::find($id);
            if ($category === null)
                return $this->failed('لا يوجد فئة بهذا الرقم');

            if ($request->get('name'))
                $category->name = $request->get('name');
            if ($parent_id == 0)
                $category->parent_id = null;
            else {
                $parent = Category::find($parent_id);
                if ($parent === null)
                    return $this->failed('لا يوجد فئة بهذا الرقم');
                $category->parent_id = $parent_id;
            }
            $category->save();

            $message = 'تم التحديث بنجاح';
            return $this->success(true, $message, $category);
        }
    }

    public function destroy($id, $key = 0)
    {
        $category = Category::find($id);
        if ($category === null && $key == 0)
            return $this->failed('لا يوجد فئة بهذا الرقم');

        $categories = Category::where('parent_id', $id)->get();
        foreach ($categories as $item) {
            echo $item->id;
            $this->destroy($item->id, 1);
        }
        Category::destroy($id);
        if ($key == 0)
            return $this->success('تم الحذف بنجاح');
    }
}
