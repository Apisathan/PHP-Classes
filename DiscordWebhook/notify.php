<?php
/**
 * User: Apisathan
 * Github: https://github.com/Apisathan
 * Date: 09-09-2018
 * Time: 23:12
 */

class notify
{
    public $content;
    public $footer;
    public $username;
    public $avatar;
    public $tts;
    public $webhook;
    public $color;
    public $data;

    public function __construct()
    {
        $this->content = "";
        $this->username = "CiviliansNetwork Refund";
        $this->avatar = "https://i.imgur.com/HUgauWf.png";
        $this->webhook = "WEBHOOK";
        $this->tts = false;
        $this->color = "ffcc00";
        $this->footer = "";
    }

    public function sendNotify() {
        $tchars = ($this->content != "") ? strlen($this->content) : 0;
        $splitted = false;
        $splitArray = [];
        foreach (get_object_vars($this) as $key => $value) {
            if($value === null) {
                return;
            }
        }
        $newArray = [];
        foreach($this->data as $key => $value) {
            $chars = strlen($value["name"])+strlen($value["value"]);
            if ($chars >= 1024) {
                $split = $this->chunk_split_arr($value["value"],1024);
                for($i = 0; $i < count($split); $i++) {
                    $tchars = $tchars+strlen($split[$i]);
                    if(!empty($splitArray)) {
                        if ($tchars >= 6000) {
                            $splitArray[count($splitArray)] = [
                                "name" => $value["name"]." (Del: ".($i+1).")",
                                "value" => $split[$i]
                            ];
                            $splitted = true;
                            $tchars = 0;
                        }else{
                            $splitArray[count($splitArray)-1][] = [
                                "name" => $value["name"]." (Del: ".($i+1).")",
                                "value" => $split[$i]
                            ];
                        }
                    }else{
                        if ($tchars >= 6000) {
                            $splitArray[0] = $newArray;
                            $splitArray[1][] = [
                                "name" => $value["name"]." (Del: ".($i+1).")",
                                "value" => $split[$i]
                            ];
                            $splitted = true;
                            $tchars = 0;
                        }else{
                            $newArray[] = [
                                "name" => $value["name"]." (Del: ".($i+1).")",
                                "value" => $split[$i]
                            ];
                        }

                    }
                }
            }else{
                $tchars = $tchars+$chars;
                if(!empty($splitArray)) {
                    if ($tchars >= 6000) {
                        $splitArray[count($splitArray)][] = $value;
                        $splitted = true;
                        $tchars = 0;
                    }else{
                        $splitArray[count($splitArray)-1][] = $value;
                    }
                }else{
                    if ($tchars >= 6000) {
                        $splitArray[0] = $newArray;
                        $splitArray[1][] = $value;
                    }else{
                        $newArray[] = $value;
                    }
                }
            }
        }
        if(empty($splitArray)) {
            $splitArray = $newArray;
        }
        $url = $this->webhook;
        if($splitted) {
            $ch = curl_init();
            foreach($splitArray as $key => $value) {
                $hookObject = json_encode([
                    "content" => ($key == 0) ? $this->content : "",
                    "username" => $this->username,
                    "avatar_url" => $this->avatar,
                    "tts" => $this->tts,
                    "embeds" => [
                        [
                            "type" => "rich",
                            "color" => hexdec($this->color),
                            "footer" => [
                                "text" => ($key == (count($splitArray)-1) ? $this->footer : "")
                            ],
                            "fields" => $value
                        ]
                    ]
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
                curl_setopt_array( $ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $hookObject,
                    CURLOPT_HTTPHEADER => [
                        "Length" => strlen( $hookObject ),
                        "Content-Type" => "application/json"
                    ]
                ]);
                curl_exec($ch);
            }
            curl_close($ch);
        } else {
            $hookObject = json_encode([
                "content" => $this->content,
                "username" => $this->username,
                "avatar_url" => $this->avatar,
                "tts" => $this->tts,
                "embeds" => [
                    [
                        "type" => "rich",
                        "color" => hexdec($this->color),
                        "footer" => [
                            "text" => $this->footer
                        ],
                        "fields" => $splitArray
                    ]
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            $ch = curl_init();
            curl_setopt_array( $ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $hookObject,
                CURLOPT_HTTPHEADER => [
                    "Length" => strlen( $hookObject ),
                    "Content-Type" => "application/json"
                ]
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    function chunk_split_arr($str, $chunklen) {
        $s = chunk_split($str, $chunklen, '|');
        $s = substr($s, 0, -1);
        return explode('|', $s);
    }
}
