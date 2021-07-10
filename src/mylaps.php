<?php


namespace datagutten\amb\laps;


use datagutten\amb\laps\exceptions\MyLapsException;
use DOMDocument;
use Requests;
use Requests_Response;
use SimpleXMLElement;

class mylaps
{
    /**
     * HTTP Get request
     * @param string $url
     * @param array $headers
     * @param array $options
     * @return Requests_Response
     * @throws MyLapsException
     */
    public static function get($url, $headers = [], $options = [])
    {
        $response = Requests::get($url, $headers, $options);
        if(!$response->success)
            throw new MyLapsException(sprintf('HTTP error %d', $response->status_code));
        else
            return $response;
    }

    /**
     * Get track activities
     * @param string $mylaps_id MyLaps track ID
     * @return SimpleXMLElement[]
     * @throws MyLapsException
     */
    public static function activities(string $mylaps_id)
    {
        $response = self::get(sprintf('https://speedhive.mylaps.com/Practice/%1$d/PracticeTrackData?id=%1$d', $mylaps_id));
        $dom=new DOMDocument;
        @$dom->loadHtml($response->body);
        $xml=simplexml_import_dom($dom);
        return $xml->xpath("/html/body//a[contains(@href,'Activity')]");
    }

    public static function activity_id($activity)
    {
        $href=(string)$activity->attributes()['href'];
        $activityId=preg_replace('#/Practice/([0-9]+)/Activity#','$1',$href);
        return (int)$activityId;
    }

    /**
     * @param $activityId
     * @return mixed
     * @throws MyLapsException
     */
    public static function activity_csv($activityId)
    {
        $response = Requests::get('https://speedhive.mylaps.com/Export/GetCsv?activityId='.$activityId);
        if(!$response->success)
            throw new MyLapsException(sprintf('HTTP error %d', $response->status_code));
        return str_replace(chr(0),'',$response->body);
    }

    /**
     * @param $activityId
     * @return array
     * @throws MyLapsException
     */
    public static function activity_info($activityId)
    {
        $response = Requests::get(sprintf('https://speedhive.mylaps.com/Practice/%d/Activity', $activityId));

        preg_match('#class="user-avatar" src="/(.+?)"#', $response->body, $avatar);
        if(!empty($avatar))
            $avatar_url = sprintf('https://speedhive.mylaps.com/%s', $avatar[1]);
        else
            $avatar_url = null;

        preg_match('#class="nickname">(.+)</span>#', $response->body, $nickname);
        if(!empty($nickname))
            $nickname = $nickname[1];
        else
            $nickname = null;

        preg_match('#class="practice-transponder">\s+Transponder \#(.+)\s+</div>#', $response->body, $transponder_name);
        if(!empty($transponder_name)) {
            $transponder_name = trim($transponder_name[1]);
            $transponder_name = html_entity_decode($transponder_name);
        }
        else
            $transponder_name = null;

        return [
            'driver_name'=>$nickname,
            'transponder_name'=>$transponder_name,
            'transponder_id'=>self::transponder_id($activityId),
            'avatar_url'=>$avatar_url,
        ];
    }

    /**
     * @param string $avatar_url URL to avatar
     * @param string $avatar_folder Avatar folder
     * @param int $transponder_id Transponder ID
     * @throws MyLapsException
     * @return string Avatar file with extension
     */
    public static function download_avatar($avatar_url, $avatar_folder, $transponder_id)
    {
        $response = Requests::head($avatar_url);
        if(!$response->success)
            throw new MyLapsException('Avatar head request error, HTTP status %d', $response->status_code);

        $type = $response->headers['Content-Type'];
        $extension = preg_replace('#image/([a-z]+)#i', '$1', $type);
        if($type===$extension)
            throw new MyLapsException('Unable to determine extension for MIME type '.$type);

        $avatar_file = sprintf('%s/%d.%s',$avatar_folder, $transponder_id, $extension);
        $response = Requests::get($avatar_url, [], ['filename'=>$avatar_file]);
        if(!file_exists($avatar_file) || !$response->success)
            throw new MyLapsException(sprintf('Avatar download error, HTTP status %d', $response->status_code));

        return $avatar_file;
    }

    /**
     * @param int $activityId
     * @return int|void
     * @throws MyLapsException
     */
    public static function transponder_id($activityId)
    {
        $csv = self::activity_csv($activityId);

        preg_match('/([0-9]{7}),/',$csv,$transponder);
        if(empty($transponder))
            throw new MyLapsException('Transponder id not found');
        return (int)$transponder[1];
    }
}