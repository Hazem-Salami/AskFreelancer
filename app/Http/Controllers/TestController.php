<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\Answer;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Validation\Rule;

class TestController extends Controller
{
    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    /*
     *
     * create test
     * Create a category test on the database
     * @return message by JsonResponse
     * */
    public function createTest(Request $request, $id)
    {
        try {

            $category = Category::find($id);
            if ($category === null)
                return $this->failed('لا يوجد فئة بهذا الرقم');
            $questions = $category->questions;
            if (count($questions) != 0)
                return $this->failed('يوجداختبار لهذه الفئة');

            $rules = [
                'questions' => 'required|array|min:1',
                'questions.*.question' => 'required|string',
                'questions.*.faultanswers' => 'required|array',
                'questions.*.faultanswers.*.answer' => 'required|string',
                'questions.*.correctanswer' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            foreach ($request->questions as $onequestion) {

                $question = Question::create([
                    'question' => $onequestion['question'],
                    'category_id' => $id,
                ]);
                $rand = rand(0,(count($onequestion['faultanswers'])-1));
                $correctAnswer_id = 0;
                foreach ($onequestion['faultanswers'] as $i=>$faultanswer) {
                    if($rand == $i){
                        $answer = Answer::create([
                            'answer' => $onequestion['correctanswer'],
                            'question_id' => $question->id,
                        ]);
                        $correctAnswer_id = $answer->id;
                    }
                    Answer::create([
                        'answer' => $faultanswer['answer'],
                        'question_id' => $question->id,
                    ]);
                }

                $question->correctAnswer_id = $correctAnswer_id;
                $question->save();
            }

            $message = 'تم إنشاء الاختبار بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * delete test
     * delete a category test on the database
     * @return message by JsonResponse
     * */
    public function deleteTest($id)
    {
        try {

            $category = Category::find($id);
            if ($category === null)
                return $this->failed('لا يوجد فئة بهذا الرقم');

            $questions = $category->questions;

            foreach ($questions as $question) {

                $correctanswer = $question->correctAnswer;
                $correctanswer->delete();

                $answers = $question->answers;

                foreach ($answers as $answer) {
                    $answer->delete();
                }

                $question->delete();
            }

            $message = 'تم حذف الاختبار بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * edit question
     * edit a question on the database
     * @return message by JsonResponse
     * */
    public function editQuestion(Request $request, $id)
    {
        try {

            $rules = [
                'question' => 'string',
                'correctanswer' => [Rule::requiredIf($request->question == NULL), 'string'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $question = Question::find($id);
            if ($question === null)
                return $this->failed('لا يوجد سؤال بهذا الرقم');

            if ($request->question) {
                $question->question = $request->question;
                $question->save();
            }

            if ($request->correctanswer) {
                $correctanswer = $question->correctAnswer;
                $correctanswer->answer = $request->correctanswer;
                $correctanswer->save();
            }

            $message = 'تم تعديل السؤال بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * edit answer
     * edit a answer on the database
     * @return message by JsonResponse
     * */
    public function editAnswer(Request $request, $id)
    {
        try {
            $rules = [
                'answer' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $answer = Answer::find($id);

            if ($request->answer) {
                $answer->answer = $request->answer;
                $answer->save();
            }

            $message = 'تم تعديل الجواب بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * check answer
     * Check the user's answers with the correct answers of the test conducted by the user
     * give the experience the percentage of the user's correct answers
     * @return Data by JsonResponse : integer
     * */
    public function checkanswer(Request $request, $id)
    {
        try {

            $skill = Skill::find($id);
            if ($skill === null)
                return $this->failed('لا توجد خبرة بهذا الرقم');

            $user = User::find(auth()->user()->id);
            if ($user->id  != $skill->user_id)
                return $this->failed('المستخدم لا يملك هذه الخبرة');

            if ($skill->rate  != 0)
                return $this->failed('المستخدم اجرى اختبار مسبقاَ لهذه الخبرة');

            $rules = [
                'answers' => 'required|array',
                'answers.*' => 'required|numeric',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $category = $skill->category;
            $questions = $category->questions;

            if (count($questions) == count($request->answers)) {
                $i = 0;
                $count = 0;
                foreach ($request->answers as $answer) {
                    if ($answer == $questions[$i]->correctAnswer_id) {
                        $count++;
                    }
                    $i++;
                }
                $skill->rate = ($count / count($questions)) * 100;
                $skill->save();
                return $this->success('skill ' . $id, $skill->rate);
            }

            return $this->failed('ان عدد الاجوبة لا يتساوى مع عدد الاسئلة');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get questions
     * returns test questions for a skill
     * @@return Data by JsonResponse : array of questions
     * */
    public function getquestions($id)
    {
        try {

            $skill = Skill::find($id);
            if ($skill === null)
                return $this->failed('لا توجد خبرة بهذا الرقم');

            $category = $skill->category;
            $questions = $category->questions;

            foreach ($questions as $question) {
                $question->answers;
            }

            return $this->success('skill ' . $id, $questions);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 
     * get questions
     * returns test questions for a category
     * @@return Data by JsonResponse : array of questions
     * */
    public function gettest($id)
    {
        try {

            $category = Category::find($id);
            if ($category === null)
                return $this->failed('لا يوجد فئة بهذا الرقم');
            $questions = $category->questions;

            foreach ($questions as $question) {
                $question->answers;
            }

            return $this->success('category ' . $id, $questions);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
