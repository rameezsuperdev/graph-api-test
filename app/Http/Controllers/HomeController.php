<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dcblogdev\MsGraph\Facades\MsGraph;
use App\Models\Emails;

class HomeController extends Controller
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
     * fetch emails from MSGraph
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        
        $pageTitle = "List";
        $perpage = config('msgraph.perpage');
        $counterInc = $skip = 0;
        $pageno = 1;
        
        $emailsResult = MsGraph::emails()->top($perpage);
        
        if($request->ajax()){
            
            $counterInc = $skip = ($request->page*$perpage);
            $pageno = $request->page+1;
            
            $emailsResult = $emailsResult->skip($skip)->get();
            
            $classhide = ((count($emailsResult['value'])+$counterInc) == $emailsResult['@odata.count']) ? 'hide' : '';
            
            // save record in table
            $this->indextEmailData($emailsResult);

            $loadmoreBtn = '<a href="javascript:void(0);"  class="btn btn-primary loadmore '.$classhide.'" data-pageno="'.$pageno.'">'.__('Read More').'</a>';
            $html = view('partials.email-list-table', compact('emailsResult','counterInc'))->render();
            return response()->json(['status'=>true,'html'=>$html,'loadmoreBtn'=>$loadmoreBtn],200);
        }

        $emailsResult = $emailsResult->skip($skip)->get();
        $classhide = ((count($emailsResult['value'])+$counterInc) == $emailsResult['@odata.count']) ? 'hide' : '';
        
        $this->indextEmailData($emailsResult);

        return view('list',compact('emailsResult','pageTitle','pageno','counterInc','classhide'));
    }

    /**
     * insert fetch email data in table
     *
     * @return void
     */
    function indextEmailData($emailsResult){
        $insertData = [];
        if(isset($emailsResult['value']) && $emailsResult['value']!=''){
            foreach($emailsResult['value'] as $val){
                $insertData[] = [
                    'subject' => $val['subject'],
                    'send_from' => $val['from']['emailAddress']['name'],
                    'send_to' => $val['toRecipients'][0]['emailAddress']['name'],
                    'send_datetime' => $val['sentDateTime']
                ];
            }
        }

        if(!empty($insertData)){
            Emails::insert($insertData);
        }
    }
}
