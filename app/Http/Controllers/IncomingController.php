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

        preg_match("/<@(\w+)>/", $input['text'], $user);

        if (!count($user)) {
            return response(null, 200);
        }

        $data = $this->computeSlackUserInfo($user);

        if (!$data['status'] || !$data['data']['ok']) {
            return response()->json([
                "text" => 'Issue with slack user profile.'
            ]);
        }
        $data = $data['data'];

        $message = $this->sendSMS($data, $input, $user);

        if (!$message['status']) {
            return response()->json([
                "text" => 'Error in sending sms.'
            ]);
        }
        $message = $message['data'];


        return response()->json([
            "text" => 'Message has been successfully sent to the user.'
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

        try {
            $message = $twilio->messages->create($data['user']['profile']['phone'], [
                "body" => str_replace($user[1], $data['user']['name'], $input['text']),
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
