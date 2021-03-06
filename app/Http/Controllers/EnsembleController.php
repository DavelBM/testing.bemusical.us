<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\updateImageUser;
use App\Http\Requests\updateInfoEnsemble;
use App\Http\Requests\repertoirRequest;
use App\Ensemble;
use Auth;
use App\User;
use App\Tag;
use App\Instrument;
use App\Style;
use App\EnsembleTag;
use App\EnsembleStyle;
use App\EnsembleInstrument;
use App\Ensemble_image;
use App\Ensemble_video;
use App\Ensemble_song;
use App\User_info;
use App\EnsembleRepertoire;
use App\Member;
use App\GigOption;
use App\Phone;
use App\Ask;
use App\Code;
use Carbon\Carbon;
use Hash;
use Mail;
use Storage;
use stdClass;
use Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EnsembleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->confirmed == 0) 
        {
            return redirect()->route('user.unconfirmed');
        }
        elseif(Auth::user()->active == 0) 
        {
            return redirect()->route('user.blocked');
        }
        elseif(Auth::user()->type == 'soloist') 
        {
            return redirect()->route('user.dashboard');
        } 
        else
        {
            $user = \Auth::user()->id;
            //Relation many to many TAGS//
            $ensemble = Ensemble::where('user_id', $user)->firstOrFail();
            $options = $ensemble->user->gig_option;
            if ($options == null) {
                $save_new_options = new GigOption;
                $save_new_options->user_id = Auth::user()->id;
                $save_new_options->listDay = 'listDay';
                $save_new_options->listWeek = 'listWeek';
                $save_new_options->month = 'month';
                $save_new_options->start = '08:00';
                $save_new_options->end = '22:00';
                $save_new_options->save();
            }
            $my_tags = $ensemble->ensemble_tags->pluck('id')->toArray();
            //Relation many to many STYLES//
            $my_styles = $ensemble->ensemble_styles->pluck('id')->toArray();
            //Relation many to many INSTRUMENTS//
            $my_instruments = $ensemble->ensemble_instruments->pluck('id')->toArray();
            //Relation many to many//
            $tags = Tag::orderBy('name', 'DES')->pluck('name', 'id');
            $instruments = Instrument::orderBy('name', 'DES')->pluck('name', 'id');
            $styles = Style::orderBy('name', 'DES')->pluck('name', 'id');
            $images = $ensemble->ensemble_images->all();
            $videos = $ensemble->ensemble_videos->all();
            $songs = $ensemble->ensemble_songs->all();

            $repertoires = $ensemble->ensemble_repertoires->all();
            $total_repertoires = EnsembleRepertoire::where('ensemble_id', $ensemble->id)->where('visible', 1)->count(); 

            $members = Member::where('ensemble_id', $ensemble->id)->get(); 
            $asks = Ask::where('user_id', $user)->get();
            $asks_count = Ask::where('user_id', $user)
                             ->where('read', 0)
                             ->count(); 

            $codes = Code::all();
            
            try{
                $phone = Phone::select('phone', 'country', 'country_code', 'confirmed', 'updated_at')->where('user_id', $user)->firstOrFail();
            } catch(ModelNotFoundException $e) {
                $phone = new stdClass();
                $phone->country = 'null';
                $phone->country_code = '';
                $phone->phone = 0;
                $phone->confirmed = 0;
            }

            $update_timestamp = Carbon::parse($phone->updated_at);
            $now_timestamp = Carbon::now();
            $now = Carbon::parse($now_timestamp);
            $minutes_diference = $update_timestamp->diffInMinutes($now);       

            $user_update_timestamp = Carbon::parse($ensemble->created_at);
            $user_now_timestamp = Carbon::now();
            $user_now = Carbon::parse($user_now_timestamp);
            $user_days_diference = $user_update_timestamp->diffInDays($now);

            return view('ensemble.dashboard')
                   ->with('ensemble', $ensemble)
                   ->with('tags', $tags)
                   ->with('instruments', $instruments)
                   ->with('styles', $styles)
                   ->with('my_tags', $my_tags)
                   ->with('my_styles', $my_styles)
                   ->with('my_instruments', $my_instruments)
                   ->with('images', $images)
                   ->with('videos', $videos)
                   ->with('songs', $songs)
                   ->with('repertoires', $repertoires)
                   ->with('total_repertoires', $total_repertoires)
                   ->with('members', $members)
                   ->with('asks', $asks)
                   ->with('asks_count', $asks_count)
                   ->with('codes', $codes)
                   ->with('phone', $phone)
                   ->with('minutes', $minutes_diference)
                   ->with('user_days', $user_days_diference);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(updateInfoEnsemble $request, $id)
    {
        $geometry = substr($request->place_geometry, 1, -1);
        $get_geometry_trimed = explode(", ", $geometry);
        $lat = $get_geometry_trimed[0];
        $lng = $get_geometry_trimed[1];

        $address = 'id:'.$request->place_id.'|address:'.$request->place_address.'|lat:'.$lat.'|long:'.$lng;

        $user = \Auth::user()->id;
        Ensemble::where('user_id', $user)
        ->update([
            'name'         => $request->name,
            'manager_name' => $request->manager,
            'type'         => $request->type,
            'about'        => $request->about,
            'summary'      => $request->summary,
            // 'phone'        => $request->phone,
            'address'      => $address,
            'location'     => $request->location,
            'mile_radious' => $request->mile_radious
        ]);
        return redirect()->route('ensemble.dashboard');
    }

    public function updateImage(Request $request, $id)
    {
        $info = [];
        $validator = Validator::make($request->all(), [
            'image' => 'image|required',
        ]);

        if ($validator->fails()) {
            $update_profile_photo_object = new stdClass();
            $update_profile_photo_object->status ='<strong style="color: red;">Select an image</strong>';
            $info[] = $update_profile_photo_object;
            return response()->json(array('info' => $info), 200);
        } else {

            $user = \Auth::user()->id;
            if($request->file('image')){
                $file = $request->file('image');
                $name = 'ensemble_picture_'.time().'.'.$file->getClientOriginalExtension();
                $name_nice = str_replace(" ","_",$name);
                $path = public_path().'/images/ensemble';
                $file->move($path, $name_nice); 
            }

            Ensemble::where('user_id', $user)
            ->update([
                'profile_picture'   => $name_nice
            ]);

            $update_profile_photo_object = new stdClass();
            $update_profile_photo_object->status ='<strong style="color: green;">Updated</strong>';
            $update_profile_photo_object->name = $name_nice;
            $info[] = $update_profile_photo_object;

            return response()->json(array('info' => $info), 200);
        }
    }

    public function storeInstruments(Request $request)
    {        
        $instruments = [];
        $ensemble_id = Auth::user()->ensemble->id;
        EnsembleInstrument::where('ensemble_id', $ensemble_id)->delete();
        
        foreach ($request->instruments as $id) 
        {
            $instrument = new EnsembleInstrument($request->all());
            $instrument->ensemble_id = $ensemble_id;
            $instrument->instrument_id = $id;
            $instrument->save(); 
        }

        $instrument_object = new stdClass();
        $instrument_object->status ='guardado';
        $instrument_object->data = $request->instruments;
        $instruments[] = $instrument_object;
        return response()->json(array('instruments' => $instruments), 200);
    }

    public function storeTags(Request $request)
    {
        $tags = [];
        $ensemble_id = Auth::user()->ensemble->id;
        EnsembleTag::where('ensemble_id', $ensemble_id)->delete();
        
        foreach ($request->tags as $id) 
        {
            $tag = new EnsembleTag($request->all());
            $tag->ensemble_id = $ensemble_id;
            $tag->tag_id = $id;
            $tag->save(); 
        }

        $tag_object = new stdClass();
        $tag_object->status ='guardado';
        $tag_object->data = $request->tags;
        $tags[] = $tag_object;
        return response()->json(array('tags' => $tags), 200);
    }

    public function storeStyles(Request $request)
    {
        $styles = [];
        $ensemble_id = Auth::user()->ensemble->id;
        EnsembleStyle::where('ensemble_id', $ensemble_id)->delete();
        
        foreach ($request->styles as $id) 
        {
            $style = new EnsembleStyle($request->all());
            $style->ensemble_id = $ensemble_id;
            $style->style_id = $id;
            $style->save(); 
        }

        $style_object = new stdClass();
        $style_object->status ='guardado';
        $style_object->data = $request->styles;
        $styles[] = $style_object;
        return response()->json(array('styles' => $styles), 200);
    }

    public function storeImages(Request $request)
    {
        $photos = [];
        $validator = Validator::make($request->all(), [
            'photos' => 'array|required',
        ]);

        if ($validator->fails()) {
            $photo_object = new stdClass();
            $photo_object->status ='<strong style="color: red;">Select an image</strong>';
            $photo_object->failed = 'true';
            $photo[] = $photo_object;
            return response()->json(array('files' => $photos), 200);
        } else {

            $imageRules = array(
                'photos' => 'image'
            );

            $ensemble_id = Auth::user()->ensemble->id;
            $num_img = Ensemble_image::where('ensemble_id', $ensemble_id)->count();

            if ($num_img < 5) {
                //dd('entre al primer filtro');
                $path = public_path().'/images/general';
                foreach ($request->photos as $photo) {
                    $photo = array('photos' => $photo);
                    $imageValidator = Validator::make($photo, $imageRules);
                    if ($imageValidator->fails()) {
                        //dd('esto fallo');
                        $photo_object = new stdClass();
                        $photo_object->status ='<strong style="color: red;">'.$photo['photos']->getClientOriginalName().' is not an image</strong>';
                        $photo_object->failed = 'true';
                        $photos[] = $photo_object;
                        break;
                    } else {

                        $filename = 'ensemble_bio_'.time().'|'.$photo['photos']->getClientOriginalName();
                        $photo['photos']->move($path, $filename);

                        $ensemble_photo = new Ensemble_image();
                        $ensemble_photo->ensemble_id = $ensemble_id;
                        $ensemble_photo->name = $filename;
                        $ensemble_photo->save();

                        $new_num_img = Ensemble_image::where('ensemble_id', $ensemble_id)->count();
                        if ($new_num_img < 5) {
                            $photo_object = new stdClass();
                            $photo_object->name = str_replace('photos/', '',$photo['photos']->getClientOriginalName());
                            $photo_object->fileName = $ensemble_photo->name;
                            $photo_object->fileID = $ensemble_photo->id;
                            $photo_object->status = '<strong style="color: green;">Saved successfully</strong>';
                            $photos[] = $photo_object;
                        }else{
                            $photo_object = new stdClass();
                            $photo_object->status = 'You just can add 5 pictures';
                            $photos[] = $photo_object;
                            break;
                        }
                    }
                }
                return response()->json(array('files' => $photos), 200); 
            } else {
                $photo_object = new stdClass();
                $photo_object->status = 'You just can add 5 pictures';
                $photos[] = $photo_object;
                return response()->json(array('files' => $photos), 200);
            }
        }  
    }

    public function destroyImageEnsemble($image)
    {
        $info = [];
        $ensemble_id = Auth::user()->ensemble->id;
        $get_name = Ensemble_image::select('name')->where('id', $image)->first();
        Ensemble_image::where('ensemble_id', $ensemble_id)->where('id', $image)->delete();
        $delete_photo_object = new stdClass();
        $get_name_array = explode("|", $get_name->name);
        $delete_photo_object->status = $get_name_array[1].' <strong style="color: red;">deleted successfully</strong>';
        $delete_photo_object->idImg = $image;
        $info[] = $delete_photo_object;

        return response()->json(array('info' => $info), 200);
    }

    public function video(Request $request)
    {
        $ensemble_id = Auth::user()->ensemble->id;
        $total_videos = Ensemble_video::where('ensemble_id', $ensemble_id)->count();
        if ($total_videos < 5) {

            $video = new Ensemble_video($request->all());
            
            //CHECK IF THIS IS A VIDEO FROM YOUTUBE
            if (strpos($request->video, 'youtube') !== false or strpos($request->video, 'youtu.be') !== false) {

                if (strpos($request->video, 'youtu.be') !== false) {
                    //IF CONTAINS YOUTUBE ID SEARCH FOR ID VIDEO
                    $display = explode("/", $request->video);
                    $id_video = end($display);
                    $video->code = $id_video;                
                }elseif (strpos($request->video, 'iframe') !== false) {
                    //IF CONTAINS YOUTUBE ID SEARCH FOR ID VIDEO
                    $display = explode("/embed/", $request->video);
                    $id_video = explode('"', $display[1]);
                    $video->code = $id_video[0];
                }elseif (strpos($request->video, 'www.youtube.com/watch?v') !== false){
                    //IF CONTAINS YOUTUBE ID SEARCH FOR ID VIDEO
                    $display = explode("=", $request->video);
                    $id_video = end($display);
                    $video->code = $id_video;
                }else{
                    $video_object = new stdClass();
                    $video_object->status = '<strong style="color: red;">That is not an allowed link or video</strong>';
                    $video_object->flag = '0';
                    $videos[] = $video_object;
                    return response()->json(array('videos' => $videos), 200);
                }

                $video->platform = 'youtube';
                $video->ensemble_id = $ensemble_id;
                $video->save();

            }elseif (strpos($request->video, 'vimeo') !== false) {
                
                if (strpos($request->video, 'iframe') !== false) {
                    //IF CONTAINS VIMEO ID, SEARCH FOR ID VIDEO
                    $display = explode('</iframe>', $request->video);
                    $display_1 = explode('/video/', $display[0]);
                    $last_link = end($display_1);
                    $id_video = explode('"', $last_link);
                    $video->code = $id_video[0];                
                }elseif(strpos($request->video, 'https://vimeo.com/') !== false){
                    //IF CONTAINS VIMEO ID, SEARCH FOR ID VIDEO
                    $display = explode("/", $request->video);
                    $id_video = end($display);
                    $video->code = $id_video;
                }else{
                    $video_object = new stdClass();
                    $video_object->status = '<strong style="color: red;">That is not an allowed link or video</strong>';
                    $video_object->flag = '0';
                    $videos[] = $video_object;
                    return response()->json(array('videos' => $videos), 200);
                }    

                $video->platform = 'vimeo';
                $video->ensemble_id = $ensemble_id;
                $video->save();

            }else{
                $video_object = new stdClass();
                $video_object->status = '<strong style="color: red;">That is not an allowed link or video</strong>';
                $video_object->flag = '0';
                $videos[] = $video_object;
                return response()->json(array('videos' => $videos), 200);
            }
        }else{
            $video_object = new stdClass();
            $video_object->status = '<strong style="color: red;">You only can add 5 videos in total</strong>';
            $video_object->flag = '0';
            $videos[] = $video_object;
            return response()->json(array('videos' => $videos), 200);
        }
        $video_object = new stdClass();
        $video_object->status = '<strong style="color: green;">Video successfully added</strong>';
        $video_object->flag = '1';
        $video_object->code = $video->code;
        $video_object->platform = $video->platform;
        $video_object->id = $video->id;
        $videos[] = $video_object;
        return response()->json(array('videos' => $videos), 200);
    }

    public function delete_video($id)
    {
        $info = [];
        $video = Ensemble_video::find($id);
        if ($video->ensemble_id == Auth::user()->ensemble->id) {
            $video->delete();
            $delete_song_object = new stdClass();
            $delete_song_object->status = '<strong style="color: red;">video deleted successfully</strong>';
            $delete_song_object->id = $id;
            $info[] = $delete_song_object;
            return response()->json(array('info' => $info), 200);
        } else {
            $delete_video_object = new stdClass();
            $delete_video_object->status = 'Action no permitted';
            $info[] = $delete_video_object;
            return response()->json(array('info' => $info), 200);
        }
    }

    public function song(Request $request)
    {
        $ensemble_id = Auth::user()->ensemble->id;
        $total_songs = Ensemble_song::where('ensemble_id', $ensemble_id)->count();

        if ($total_songs < 5) {

            $song = new Ensemble_song($request->all());
            //CHECK IF THIS IS A VIDEO FROM SPOTIFY
            if (strpos($request->song, 'spotify') !== false){

                if (strpos($request->song, 'open.spotify') !== false) {
                    $display = explode("/track/", $request->song);
                    $id_song = end($display);
                    $song->code = $id_song; 
                }elseif (strpos($request->song, 'spotify:track') !== false) {
                    $display = explode(":", $request->song);
                    $id_song = end($display);
                    $song->code = $id_song;
                }elseif (strpos($request->song, 'embed.spotify.com') !== false) {
                    $display = explode("%3Atrack%3A", $request->song);
                    $id_song = explode('"', $display[1]);
                    $song->code = $id_song[0];
                }else{
                    $song_object = new stdClass();
                    $song_object->status = '<strong style="color: red;">That is not an allowed link or song</strong>';
                    $song_object->flag = '0';
                    $songs[] = $song_object;
                    return response()->json(array('songs' => $songs), 200);
                }
                $song->platform = 'spotify';
                $song->ensemble_id = $ensemble_id;
                $song->save();

            }elseif (strpos($request->song, 'soundcloud') !== false) {
                
                if (strpos($request->song, 'iframe') !== false) {
                    $display = explode("api.soundcloud.com/tracks/", $request->song);
                    $id_song = explode("&amp;", $display[1]);
                    $song->code = $id_song[0];
                }else {
                    $song_object = new stdClass();
                    $song_object->status = '<strong style="color: red;">That is not an allowed link or song</strong>';
                    $song_object->flag = '0';
                    $songs[] = $song_object;
                    return response()->json(array('songs' => $songs), 200);
                }     
                $song->platform = 'soundcloud';   
                $song->ensemble_id = $ensemble_id;
                $song->save();

            }else{
                $song_object = new stdClass();
                    $song_object->status = '<strong style="color: red;">That is not an allowed link or song</strong>';
                    $song_object->flag = '0';
                    $songs[] = $song_object;
                    return response()->json(array('songs' => $songs), 200);
            }
        }else{
            $song_object = new stdClass();
            $song_object->status = '<strong style="color: red;">You only can add 5 songs in total</strong>';
            $song_object->flag = '0';
            $songs[] = $song_object;
            return response()->json(array('songs' => $songs), 200);
        }
        $song_object = new stdClass();
        $song_object->status = '<strong style="color: green;">song successfully added</strong>';
        $song_object->flag = '1';
        $song_object->code = $song->code;
        $song_object->platform = $song->platform;
        $song_object->id = $song->id;
        $songs[] = $song_object;
        return response()->json(array('songs' => $songs), 200);
    }


    public function delete_song($id)
    {
        $info = [];
        $song = Ensemble_song::find($id);
        if ($song->ensemble_id == Auth::user()->ensemble->id) {
            $song->delete();
            $delete_song_object = new stdClass();
            $delete_song_object->status = '<strong style="color: red;">song deleted successfully</strong>';
            $delete_song_object->id = $id;
            $info[] = $delete_song_object;
            return response()->json(array('info' => $info), 200);
        } else {
            $delete_song_object = new stdClass();
            $delete_song_object->status = 'Action no permitted';
            $info[] = $delete_song_object;
            return response()->json(array('info' => $info), 200);
        }
    }

    public function repertoir(Request $request)
    {   
        $info = [];
        $validator = Validator::make($request->all(), [
            'composer' => 'required|max:50',
            'work' => 'required|max:50',
        ]);

        if ($validator->fails()) {
            $repertoir_object = new stdClass();
            $repertoir_object->status = '<strong style="color: red;"> 50 is the max number of caracters</strong>';
            $info[] = $repertoir_object;
            return response()->json(array('info' => $info), 200);
        } else {
            $repertoir = new EnsembleRepertoire($request->all());
            $repertoir->ensemble_id = Auth::user()->ensemble->id;
            $repertoir->repertoire_example = $request->work.' - '.$request->composer;
            $repertoir->visible = 0;
            $repertoir->save();

            $repertoir_count = EnsembleRepertoire::where('ensemble_id', Auth::user()->ensemble->id)->where('visible', 1)->count();

            $repertoir_object = new stdClass();
            $repertoir_object->status = '<strong style="color: green;">Repertoir "'.$request->work.' - '.$request->composer.'" successfully added</strong>';
            $repertoir_object->name = $request->work.' - '.$request->composer;
            $repertoir_object->id = $repertoir->id;
            $repertoir_object->count = $repertoir_count;
            $info[] = $repertoir_object;
            return response()->json(array('info' => $info), 200);
        }
    }

    public function destroy_repertoir($id)
    {
        $info = [];
        $repertoir = EnsembleRepertoire::find($id);
        if ($repertoir->ensemble_id == Auth::user()->ensemble->id) {
            $repertoir->delete();
            $delete_repertoir_object = new stdClass();
            $delete_repertoir_object->status = '<strong style="color: red;">Repertoir deleted successfully</strong>';
            $delete_repertoir_object->id = $id;
            $info[] = $delete_repertoir_object;
            return response()->json(array('info' => $info), 200);
        } else {
            $delete_repertoir_object = new stdClass();
            $delete_repertoir_object->status = 'Action no permitted';
            $info[] = $delete_repertoir_object;
            return response()->json(array('info' => $info), 200);
        }
    }

    public function update_repertoir($id)
    {
        $repertoir = EnsembleRepertoire::select('visible')->find($id);
        $repertoir->visible = !$repertoir->visible;
        EnsembleRepertoire::find($id)->update(['visible' => $repertoir->visible]);
        return redirect()->route('ensemble.dashboard');
    }

    public function member(Request $request)
    {
        if(filter_var($request->member, FILTER_VALIDATE_EMAIL)) {
            //USERS NOT REGISTERED YET

            if (!User::where('email', '=', $request->member)->exists()) {
             
                $ensemble = Ensemble::select('id', 'name')
                                    ->where('user_id', Auth::user()->id)
                                    ->firstOrFail();
                
                $num_code = str_random(50);
                $token = $num_code.time();

                if( Member::where('ensemble_id', '=', $ensemble->id)
                    ->where('email', '=', $request->member)
                    ->where('confirmation', '=', 1)
                    ->exists()
                  )
                {
                    return redirect()->back()->withErrors(['member'=>"This user is part of your ensemble already"]);
                }

                $member = new Member;
                $member->ensemble_id  = $ensemble->id;
                $member->user_id      = Auth::user()->id;
                $member->name         = 'new';
                $member->instrument   = 'null';
                $member->slug         = 'null';
                $member->token        = $token;
                $member->email        = $request->member;
                $member->confirmation = 0;
                $member->save();

                $data = [  
                            'token'           => $token,
                            'ensemble_name'   => $ensemble->name,
                            'email'           => $request->member,
                        ];

                Mail::send('email.notmember_request', $data, function($message) use ($request){
                    $message->from('support@bemusical.us');
                    $message->to($request->member);
                    $message->subject('You have an invitation from BeMusical.us member');
                });

            }else{
                $num_code = str_random(50);
                $token = $num_code.time();
                $user_entered = User::where('email', $request->member)->first();

                $user = User_info::where('user_id', '=', $user_entered->id)->firstOrFail();

                $ensemble = Ensemble::select('id', 'name')
                                    ->where('user_id', Auth::user()->id)
                                    ->firstOrFail();

                if(   Member::where('ensemble_id', '=', $ensemble->id)
                            ->where('user_id', '=', $user->user->id)
                            ->exists()
                  )
                {
                    return redirect()->back()->withErrors(['member'=>"This user is part of your ensemble already"]);
                }

                $member = new Member;
                $member->ensemble_id  = $ensemble->id;
                $member->user_id      = $user->user->id;
                $member->name         = $user->first_name.' '.$user->last_name;
                $member->instrument   = 'null';
                $member->slug         = $user->slug;
                $member->token        = $token;
                $member->email        = $user->user->email;
                $member->confirmation = 0;
                $member->save();
                
                $data = [  
                            'token'           => $token,
                            'ensemble_name'   => $ensemble->name,
                            'name'            => $user->first_name,
                        ];

                Mail::send('email.member_request', $data, function($message) use ($user){
                    $message->from('support@bemusical.us');
                    $message->to($user->user->email);
                    $message->subject('You have an invitation');
                });
                return redirect()->route('ensemble.dashboard');
            }
            return redirect()->route('ensemble.dashboard');

        } else {
            //USERS ALREADY REGISTERED
            if(strpos($request->member, 'bemusical.us/') !== false) {
                $display = explode("bemusical.us/", $request->member);
                $slug_member = end($display);
                
                if (Ensemble::where('slug', '=', $slug_member)->exists()) {
                    return redirect()->back()->withErrors(['member'=>"You cannot add ensembles in this ensemble"]);
                }elseif(User_info::where('slug', '=', $slug_member)->exists()){
                    $num_code = str_random(50);
                    $token = $num_code.time();
                    $user = User_info::where('slug', '=', $slug_member)->firstOrFail();

                    $ensemble = Ensemble::select('id', 'name')
                                        ->where('user_id', Auth::user()->id)
                                        ->firstOrFail();

                    if(   Member::where('ensemble_id', '=', $ensemble->id)
                                ->where('user_id', '=', $user->user->id)
                                ->exists()
                      )
                    {
                        return redirect()->back()->withErrors(['member'=>"This user is part of your ensemble already"]);
                    }

                    $member = new Member;
                    $member->ensemble_id  = $ensemble->id;
                    $member->user_id      = $user->user->id;
                    $member->name         = $user->first_name.' '.$user->last_name;
                    $member->instrument   = 'null';
                    $member->slug         = $slug_member;
                    $member->token        = $token;
                    $member->email        = $user->user->email;
                    $member->confirmation = 0;
                    $member->save();
                    
                    $data = [  
                                'token'           => $token,
                                'ensemble_name'   => $ensemble->name,
                                'name'            => $user->first_name,
                            ];

                    Mail::send('email.member_request', $data, function($message) use ($user){
                        $message->from('support@bemusical.us');
                        $message->to($user->user->email);
                        $message->subject('You have an invitation');
                    });
                }else{
                    return redirect()->back()->withErrors(['member'=>"The user does not exist"]);
                }

            }else{
                return redirect()->back()->withErrors(['member'=>"Link not allowed"]);
            }

            return redirect()->route('ensemble.dashboard');
        }
    }

    public function destroy_member($id)
    {
        $member = Member::find($id)->delete();
        return redirect()->route('ensemble.dashboard');
    }
}
