<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\RestException;

class IncomingController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $input = $request->all();

        preg_match("/<@(\w+)|/", $input['text'], $user);

        $response = [
            "response_type" => "in_channel"
        ];
        if (!count($user) || count($user) == 1) {

            if ($input['text'] == "") {
                $response['text'] = 'Looks good.. :blush:';
            }
            return response()->json($response);
        }

        $data = $this->computeSlackUserInfo($user);

        if (!$data['status'] || !$data['data']['ok']) {
            return response()->json([
                "response_type" => "in_channel",
                "text" => 'Issue with slack user profile :zipper_mouth_face:'
            ]);
        }
        $data = $data['data'];

        $message = $this->sendSMS($data, $input, $user);
        if (!$message['status']) {
            return response()->json([
                "response_type" => "in_channel",
                "text" => 'Error in sending sms :x:'
            ]);
        }
        $message = $message['data'];


        return response()->json([
            "response_type" => "in_channel",
            "text" => 'Message has been successfully sent to the user :heavy_check_mark:'
        ]);
    }

    public function computeSlackUserInfo($user)
    {
        try {
            $client = new Client(); //GuzzleHttp\Client
            $result = $client->request('GET', 'https://slack.com/api/users.info', [
                'query' => [
                    'token' => env('SLACK_WORKSPACE_TOKEN'),
                    'user' => $user[1],
                ]
            ]);

            $data = json_decode($result->getBody()->getContents(), true);
            return ['status' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            // echo $e->getResponse()->getBody()->getContents();
            // echo "\n";
            return ['status' => false];
        }
        return ['status' => false];
    }

    public function sendSMS($data, $input, $user)
    {
        $accountSid = config('app.twilio')['TWILIO_ACCOUNT_SID'];
        $authToken  = config('app.twilio')['TWILIO_AUTH_TOKEN'];
        $from  = config('app.twilio')['TWILIO_SMS_NUMBER'];
        $twilio = new TwilioClient($accountSid, $authToken);
        $body = str_replace('<@'.$user[1].'|', '@', $input['text']);
        $body = str_replace('@'.$data['user']['name'].'>', '@'.$data['user']['name'], $body);
        try {
            $message = $twilio->messages->create($data['user']['profile']['phone'], [
                "body" => $body,
                "from" => $from
            ]);
            return ['status' => true, 'data' => $message];
        } catch (RestException $e) {
            // echo "Error: " . $e->getMessage();
            return ['status' => false];
        }
        return ['status' => false];
    }
}
