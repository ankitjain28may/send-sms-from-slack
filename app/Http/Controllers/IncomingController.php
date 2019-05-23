<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Twilio\Rest\Client as TwilioClient;

class IncomingController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $accountSid = config('app.twilio')['TWILIO_ACCOUNT_SID'];
        $authToken  = config('app.twilio')['TWILIO_AUTH_TOKEN'];
        $from  = config('app.twilio')['TWILIO_SMS_NUMBER'];

        $input = $request->all();

        preg_match("/<@(\w+)>/", $input['text'], $user);

        try {
            $client = new Client(); //GuzzleHttp\Client
            $result = $client->request('GET', 'https://slack.com/api/users.info', [
                'query' => [
                    'token' => env('SLACK_WORKSPACE_TOKEN'),
                    'user' => $user[1],
                ]
            ]);

            $data = json_decode($result->getBody()->getContents(), true);
            // return $data;
        } catch (GuzzleException $e) {
            echo $e->getResponse()->getBody()->getContents();
            echo "\n";
        }
        try {
            $twilio = new TwilioClient($accountSid, $authToken);
            $message = $twilio->messages->create($data['user']['profile']['phone'], [
                "body" => str_replace($user[1], $data['user']['name'], $input['text']),
                "from" => $from
            ]);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }


        return response()->json([
            "text" => 'Message has been successfully sent to the user.'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
