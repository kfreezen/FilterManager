<?php

namespace App\Http\Controllers;
use App\AppGroup;
use App\Group;
use App\AppGroupToApp;
use App\App;
use Illuminate\Http\Request;
use App\UserGroupToAppGroup;
use Log;
class ApplicationGroupController extends Controller
{
    public function __construct() {
        
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $app_groups = AppGroup::with('group_app')->get();
        foreach($app_groups as $app_group) {
            $arr_app_id = [];
            foreach($app_group->group_app as $group_item) {
                $arr_app_id[] = $group_item->app_id;
            }
            Log::debug($arr_app_id);
            $apps = App::whereIn('id', $arr_app_id)->get();
            $str = '';
            foreach($apps as $app) {
                if(strlen($str) > 0) {
                    $str .= ", ".$app->name;
                } else {
                    $str = $app->name;
                }
            }
            if (strlen($str) == 0) {
                $str = "-";
            }
            $app_group->app_names = $str;
        }
        return response()->json($app_groups);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, [
            'group_name' => 'required'
        ]);

        $input = $request->only(['group_name']);
        $group = AppGroup::create($input);
        $apps_str = $request->only(['apps']);
        $apps = array_filter(explode(",", $apps_str['apps']));

        foreach ($apps as $app_id) {
            AppGroupToApp::create( array(
                "app_group_id" => $group->id,
                "app_id" => intval($app_id)
            ));
        }

        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        AppGroupToApp::where('app_group_id', $id)->delete();
        UserGroupToAppGroup::where('app_group_id', $id)->delete();
        $applicationGroup = AppGroup::where('id', $id)->first();
        if (!is_null($applicationGroup)) {
            $applicationGroup->delete();
        }

        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        $input = $request->only(['group_name']);
        AppGroup::where('id', $id)->update($input);
        AppGroupToApp::where('app_group_id', $id)->delete();
        $apps_str = $request->only(['apps']);
        $apps = array_filter(explode(",", $apps_str['apps']));   
        foreach ($apps as $app_id) {
            AppGroupToApp::create( array(
                "app_group_id" => $id,
                "app_id" => intval($app_id)
            ));
        }
        return response('', 204);
    }
}
