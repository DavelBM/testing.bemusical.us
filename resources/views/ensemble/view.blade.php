@extends('layouts.app')

@section('logout')
    @if(Auth::guard('web')->check())
        <a href="{{ url('/user/logout') }}">Logout user</a>
    @endif
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                
                <div class="panel-heading">public ENSEMBLE page</div>

                <div class="panel-body">@if(!$errors->isEmpty())
                        <span class="help-block">
                            <strong style="color: red;">We had a problem while we was send your request, check again</strong>
                        </span>
                    @endif
                    @include('flash::message')
                    <div class="col-md-5">
                        <img src="{{ asset("images/ensemble/$ensemble->profile_picture") }}" class="img-circle float-left" alt="{{$ensemble->profile_picture}}" width="250" height="250">
                    </div>
                    <div class="col-md-7">
                        <!-- Displaying data -->
                        <strong>Ensemble:</strong> {{$ensemble->name}}<br>
                        <strong>Type of ensemble:</strong> {{$ensemble->type}}<br>
                        <strong>Bio summary:</strong> {{$ensemble->summary}}<br>
                        <strong>Location:</strong> {{$ensemble->location}}<br>
                        <strong>Mile Radius:</strong> {{$ensemble->mile_radious}} miles<br>
                        <strong>About Me:</strong> {{$ensemble->about}}<br>
                        <!-- /Displaying data -->
                    </div>
                </div>
                <div class="panel-body">
                    @if(!empty($ensemble->members))
                        <strong>Members</strong><br>
                        @foreach($ensemble->members as $member)
                            @if($member->confirmation == 1)
                            <a class="btn" href="{{ URL::to('/'.$member->slug) }}">{{$member->name}}</a>
                            @endif
                        @endforeach
                    @endif
                </div>
                <div class="panel-body">
                    <div class="col-md-4">
                        <strong>TAGS</strong><br>
                        @foreach($ensemble->ensemble_tags as $tag)
                            {{$tag->name}}<br>
                        @endforeach
                    </div>
                    <div class="col-md-4">
                        <strong>STYLES</strong><br>
                        @foreach($ensemble->ensemble_styles as $style)
                            {{$style->name}}<br>
                        @endforeach
                    </div>
                    <div class="col-md-4">
                        <strong>INSTRUMENTS</strong><br>
                        @foreach($ensemble->ensemble_instruments as $instrument)
                            {{$instrument->name}}<br>
                        @endforeach
                    </div>
                </div>
                <div class="panel-body">
                    <div class="col-md-12">
                        @foreach($ensemble->ensemble_images as $image)
                                <img src="{{ asset("images/general/$image->name") }}" class="img-rounded" alt="{{$image->name}}" width="304" height="236">
                        @endforeach
                    </div>
                </div>
                <div class="panel-body">
                    <strong>VIDEOS</strong>
                    <div class="col-md-12">
                    @foreach($ensemble->ensemble_videos as $video)
                        @if($video->platform == 'youtube')
                            <iframe width="100%" height="315" src="https://www.youtube.com/embed/{{$video->code}}" frameborder="0" allowfullscreen></iframe>
                        @elseif($video->platform == 'vimeo')
                            <iframe src="https://player.vimeo.com/video/{{$video->code}}" width="100%" height="315" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                        @endif
                    @endforeach
                    </div>
                </div>
                <div class="panel-body">
                    <strong>SONGS</strong>
                    <div class="col-md-12">
                    @foreach($ensemble->ensemble_songs as $song)
                        @if($song->platform == 'spotify')
                            <iframe src="https://open.spotify.com/embed?uri=spotify:track:{{$song->code}}&theme=white&view=coverart" 
                            frameborder="0" allowtransparency="true"></iframe>
                        @elseif($song->platform == 'soundcloud')
                            <iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/{{$song->code}}&amp;color=0066cc&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false"></iframe>
                        @endif
                    @endforeach                        
                    </div>
                </div>
                <div class="panel-body">
                    <strong>Repertoir</strong>
                    <div class="col-md-12">
                    @foreach($ensemble->ensemble_repertoires as $repertoire)
                        @if($repertoire->visible)
                            *{{ $repertoire->repertoire_example }}<br>
                        @endif
                    @endforeach                        
                    </div>
                </div> 

                <hr>

                <div class="panel-body">
                    <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#formRequest">Do you want to hire {{$ensemble->name}}?</button>            
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ModalForm -->
<div id="formRequest" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Fill the spaces please.</h4>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'specific.request', 'method' => 'POST']) !!}

                    <div class="row form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                        <label for="name" class="col-md-4 control-label">Name</label>

                        <div class="col-md-6">
                            <input id="name" type="text" class="form-control" name="name" placeholder="Your full name" required>

                            @if ($errors->has('name'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <label for="email" class="col-md-4 control-label">Email</label>

                        <div class="col-md-6">
                            <input id="email" type="email" class="form-control" name="email" placeholder="Your email" required>

                            @if ($errors->has('email'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group{{ $errors->has('company') ? ' has-error' : '' }}">
                        <label for="company" class="col-md-4 control-label">Company</label>

                        <div class="col-md-6">
                            <input id="company" type="text" class="form-control" name="company" placeholder="Your company" required>

                            @if ($errors->has('company'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('company') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="phone" class="col-md-4 control-label">Phone (optional)</label>

                        <div class="col-md-6">
                            <input id="phone" type="number" class="form-control" name="phone" placeholder="This makes the process faster">
                            @if ($errors->has('phone'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('phone') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="event_type" class="col-md-4 control-label">Music</label>

                        <div class="col-md-6">
                            <input id="event_type" type="text" class="form-control" name="event_type" placeholder="What kind of music do you require?" required>
                            @if ($errors->has('event_type'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('event_type') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="day" class="col-md-4 control-label">Day of performance</label>

                        <div class="col-md-6">
                            <!-- <input id="day" type="text" class="form-control" placeholder="Select date" class="textbox-n"  onfocus="(this.type='date')" name="day"> --> 
                            <input id="day" type="text" class="form-control" placeholder="Select date" type="date" name="day">
                            <!-- <input id="day" type="text" class="form-control" placeholder="Select date" name="day">
                            <select id="day" type="text" class="form-control" placeholder="Select date" name="day" required> -->

                            </select>
                            @if ($errors->has('day'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('day') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="time" class="col-md-4 control-label">Time of performance</label>

                        <div class="col-md-6">
                            <!-- <input id="time" class="time form-control" name="time" placeholder="Select time" required> -->
                            <select id="time" class="time form-control" name="time" required>
                                <option value="0:00">Select time</option>
                                <option value="8:00">8:00AM</option>
                                <option value="8:15">8:15AM</option>
                                <option value="8:30">8:30AM</option>
                                <option value="8:45">8:45AM</option>
                                <option value="9:00">9:00AM</option>
                                <option value="9:15">9:15AM</option>
                                <option value="9:30">9:30AM</option>
                                <option value="9:45">9:45AM</option>
                                <option value="10:00">10:00AM</option>
                                <option value="10:15">10:15AM</option>
                                <option value="10:30">10:30AM</option>
                                <option value="10:45">10:45AM</option>
                                <option value="11:00">11:00AM</option>
                                <option value="11:15">11:15AM</option>
                                <option value="11:30">11:30AM</option>
                                <option value="11:45">11:45AM</option>
                                <option value="12:00">12:00PM</option>
                                <option value="12:15">12:15PM</option>
                                <option value="12:30">12:30PM</option>
                                <option value="12:45">12:45PM</option>
                                <option value="13:00">1:00PM</option>
                                <option value="13:15">1:15PM</option>
                                <option value="13:30">1:30PM</option>
                                <option value="13:45">1:45PM</option>
                                <option value="14:00">2:00PM</option>
                                <option value="14:15">2:15PM</option>
                                <option value="14:30">2:30PM</option>
                                <option value="14:45">2:45PM</option>
                                <option value="15:00">3:00PM</option>
                                <option value="15:15">3:15PM</option>
                                <option value="15:30">3:30PM</option>
                                <option value="15:45">3:45PM</option>
                                <option value="16:00">4:00PM</option>
                                <option value="16:15">4:15PM</option>
                                <option value="16:30">4:30PM</option>
                                <option value="16:45">4:45PM</option>
                                <option value="17:00">5:00PM</option>
                                <option value="17:15">5:15PM</option>
                                <option value="17:30">5:30PM</option>
                                <option value="17:45">5:45PM</option>
                                <option value="18:00">6:00PM</option>
                                <option value="18:15">6:15PM</option>
                                <option value="18:30">6:30PM</option>
                                <option value="18:45">6:45PM</option>
                                <option value="19:00">7:00PM</option>
                                <option value="19:15">7:15PM</option>
                                <option value="19:30">7:30PM</option>
                                <option value="19:45">7:45PM</option>
                                <option value="20:00">8:00PM</option>
                                <option value="20:15">8:15PM</option>
                                <option value="20:30">8:30PM</option>
                                <option value="20:45">8:45PM</option>
                                <option value="21:00">9:00PM</option>
                                <option value="21:15">9:15PM</option>
                                <option value="21:30">9:30PM</option>
                                <option value="21:45">9:45PM</option>
                                <option value="22:00">10:00PM</option>
                            </select>
                            @if ($errors->has('time'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('time') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="duration" class="col-md-4 control-label">Length of performance</label>

                        <div class="col-md-6">

                            <select id="duration" class="form-control" name="duration" placeholder="Minutes" required>
                                <option value="0">Select the duration</option>
                                <option value="60">1 hr</option>
                                <option value="90">1 hr 30 min</option>
                                <option value="120">2 hrs</option>
                                <option value="150">2 hrs 30 min</option>
                                <option value="180">3 hrs</option>
                                <option value="210">3 hr 30 min</option>
                                <option value="240">4 hrs</option>
                                <option value="270">4 hr 30 min</option>
                                <option value="300">5 hrs</option>
                            </select>
                            <!-- <input id="duration" type="number" class="form-control" name="duration" placeholder="Minutes" required> -->
                            @if ($errors->has('duration'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('duration') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="address" class="col-md-4 control-label">Location of event</label>

                        <div class="col-md-6">
                            <input id="searchTextField" type="text" class="form-control" name="address" required>
                            @if ($errors->has('address'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('address') }}</strong>
                                </span>
                            @endif
                            @if ($errors->has('place_id'))
                                <span class="help-block">
                                    <strong style="color: red;">Please choose a place with google suggestions</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <input id="place-id" type="hidden" name="place_id" required>
                    <input id="place-address" type="hidden" name="place_address" required>
                    <input id="place-geometry" type="hidden" name="place_geometry" required>
                    <input id="user_id" type="hidden" class="form-control" name="user_id" value="{{$ensemble->user->id}}">

                    <div class="form-group">
                        {!! Form::submit('Ask availability', ['class' => 'btn btn-primary']) !!}
                    </div>

                {!! Form::close() !!}
            </div>
        </div>

    </div>
</div>
<!-- /ModalForm -->
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/jquery.timepicker.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.css') }}">
@endsection

@section('js')
    <script src="{{ asset('js/main.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.js') }}"></script>
    <!-- <script type="text/javascript" src="{{ asset('js/jquery.timepicker.min.js') }}"></script> -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAiSpxjqWzkCFUzn6l1H-Lh-6mNA8OnKzI&v=3.exp&libraries=places"></script>
@endsection

@section('script')
    //////////////Maps////////////////////
    function initialize() {

    var input = document.getElementById('searchTextField');
    var autocomplete = new google.maps.places.Autocomplete(input);

    autocomplete.addListener('place_changed', function() {
        var place = autocomplete.getPlace();
        if (!place.geometry) {
            return;
        }

        document.getElementById('place-id').value = place.place_id;
        document.getElementById('place-geometry').value = place.geometry.location;
        document.getElementById('place-address').value = place.formatted_address;
      });
    }

    google.maps.event.addDomListener(window, 'load', initialize);
    //////////////----////////////////////
    //////////////Getting Date////////////////////
    var currentdate = new Date(); 

    var todaymonth = (currentdate.getMonth()+1);
    if(todaymonth == "1"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "2"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "3"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "4"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "5"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "6"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "7"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "8"){
        var newmonth = '0'+todaymonth;
    }else if(todaymonth == "9"){
        var newmonth = '0'+todaymonth;
    }

    var todayday = currentdate.getDate();
    if(todayday == "1"){
        var newday = '0'+todayday;
    }else if(todayday == "2"){
        var newday = '0'+todayday;
    }else if(todayday == "3"){
        var newday = '0'+todayday;
    }else if(todayday == "4"){
        var newday = '0'+todayday;
    }else if(todayday == "5"){
        var newday = '0'+todayday;
    }else if(todayday == "6"){
        var newday = '0'+todayday;
    }else if(todayday == "7"){
        var newday = '0'+todayday;
    }else if(todayday == "8"){
        var newday = '0'+todayday;
    }else if(todayday == "9"){
        var newday = '0'+todayday;
    }

    var datetime =  currentdate.getFullYear()+"-"+newmonth+"-"+newday;
    document.getElementById('day').setAttribute("min", datetime);
    //////////////------------////////////////////
    $('#day').datepicker({
        'format': 'yyyy-mm-dd',
        'autoclose': true,
    });

@endsection
