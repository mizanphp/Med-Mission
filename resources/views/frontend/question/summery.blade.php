@extends('layouts.master')

@section('content')

    @php($question_paper_type = substr(Route::currentRouteName(), 0, strpos(Route::currentRouteName(), ".")))

    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-8">
            <ol class="breadcrumb">
                <li>
                    <a @if($question_paper_type == 'practice') href="{{ route('study.select-subject') }}" @endif>{{ ucfirst($question_paper_type) }}</a>
                </li>
                <li class="active">
                    <strong>Summery</strong>
                </li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Summery Details</h5>
                        @if(!Session::get('limit_cross'))
                            @if($question_paper_type == 'practice')
                                <a onclick="restartPractice()" href="javascript:void(0)" class="btn btn-sm btn-warning pull-right m-t-n-xs" type="button">
                                    <i class="fa fa-refresh"></i> <strong>Restart</strong>
                                </a>
                                <form id="finished-form" method="POST" action="{{ route($question_paper_type.'.question.restart') }}" style="display: none" >
                                    @csrf()
                                </form>
                            @endif
                        @endif
                    </div>

                    <div class="ibox-content">

                        @php($question_paper_type = substr(Route::currentRouteName(), 0, strpos(Route::currentRouteName(), ".")))

                        @include('flash-messages.flash-messages')

                        @if(!Session::get('limit_cross'))
                            <div class="row">
                                <div class="col-sm-4">
                                    <ul class="list-group">
                                        <li class="list-group-item">
                                            <span class="badge badge-success">{{ $subject->name }}</span>
                                            Subject Name
                                        </li>
                                        <li class="list-group-item ">
                                            <span class="badge badge-info">{{ count($total_questions) }}</span>
                                            Total Question
                                        </li>
                                        <li class="list-group-item">
                                            <span class="badge badge-primary">{{ $right_answer }}</span>
                                            Right Answer
                                        </li>
                                        <li class="list-group-item">
                                            <span class="badge badge-primary">{{ ($right_answer*$per_question_mark)-($wrong_answer*$subject->questionTemplates->first()->negative_marks) }}</span>
                                            Obtain Marks
                                        </li>
                                        <li class="list-group-item">
                                            <span class="badge badge-warning">{{ $wrong_answer }}</span>
                                            Wrong Answer
                                        </li>
                                        @if($question_paper_type == 'examination')
                                            <li class="list-group-item">
                                                <span class="badge badge-danger">{{ $subject->questionTemplates->first()->negative_marks }}</span>
                                                Negative Marks/Question
                                            </li>
                                            <li class="list-group-item">
                                                <span class="badge badge-danger">{{ $wrong_answer*$subject->questionTemplates->first()->negative_marks }}</span>
                                                Negative Marks
                                            </li>
                                        @endif
                                    </ul>
                                </div>

                                @php($i=1)
                                <div class="col-sm-8">
                                    @foreach($total_questions->chunk(2) as $k => $questions)
                                        <div class="row">
                                            @foreach($questions as $question)
                                                <div class="col-sm-6">
                                                    <div class="form-group {{ $question->is_correct_answer ? '' : 'error_answer' }}">
                                                        <label class="m-b-sm" style="font-size: 14px"><b>{{ $i }}.</b> {{ $question->question }}</label>
                                                        @if($question->question_type_id == 1)
                                                            @foreach($question->options as $option)
                                                                <div class=" {{ in_array($option->id, $question->true_correct_answers) ? 'text-primary' : (in_array($option->id, $question->true_student_answer) ? 'text-danger' : '') }}">
                                                                    <label>
                                                                        <input {{ in_array($option->id, $question->true_student_answer) ? 'checked' : '' }} type="checkbox" disabled="true">
                                                                        <i></i> {{ $option->option }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        @endif

                                                        @if($question->question_type_id == 2)
                                                            <?php $i=1; ?>
                                                            @foreach($question->options as $option)
                                                                @if($i==1)
                                                                    <div>True False</div>
                                                                @endif

                                                                <?php
                                                                $option_class = '';
                                                                $answer_is = '';

                                                                if((in_array($option->id, $question->true_student_answer) && in_array($option->id, $question->true_correct_answers)) ||
                                                                    (in_array($option->id, $question->false_student_answer) && in_array($option->id, $question->false_correct_answers)))
                                                                {
                                                                    $option_class = 'text-primary';
                                                                }elseif(in_array($option->id, $question->true_student_answer) || in_array($option->id, $question->false_student_answer)){
                                                                    $option_class = 'text-danger';
                                                                }


                                                                if($option_class!='text-primary'){
                                                                    if(in_array($option->id, $question->true_correct_answers)){
                                                                        $answer_is = '<span style="color:#a94442"> Ans:true</span>';
                                                                    }elseif(in_array($option->id, $question->false_correct_answers)){
                                                                        $answer_is = '<span style="color:#a94442"> Ans:false</span>';
                                                                    }
                                                                }

                                                                ?>
                                                                <div class="{{$option_class}}">
                                                                    <label>
                                                                        <input {{ !empty($question->true_student_answer) && in_array($option->id, $question->true_student_answer) ? 'checked' : '' }} type="checkbox" disabled="true"> &nbsp;&nbsp;&nbsp;
                                                                        <input {{ !empty($question->false_student_answer) && in_array($option->id, $question->false_student_answer) ? 'checked' : '' }} type="checkbox" disabled="true"> &nbsp;&nbsp;&nbsp;
                                                                        <i></i> {{ $option->option }} <?php echo $answer_is ?>
                                                                    </label>
                                                                </div>
                                                            <?php $i++ ?>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                                @php($i++)
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom-js')
    <script>
        //show confirm message when delete table row
        function restartPractice() {
            swal({
                title: "Are you sure?",
                text: "You want to finished yor practice for today!",
                type: "warning",
                showCancelButton: true,
                allowOutsideClick: true,
                confirmButtonColor: "#1ab394",
                confirmButtonText: "Yes, finished practice!",
                closeOnConfirm: true
            }, function () {
                document.getElementById('finished-form').submit();
            });
        }
    </script>
@endsection
