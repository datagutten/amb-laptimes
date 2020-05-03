<?php


namespace datagutten\amb\laps;


use datagutten\amb\laps\exceptions\MyLapsException;
use DOMDocument;
use Requests;
use Requests_Response;
use SimpleXMLElement;

class mylaps
{
    public $mylaps_id;
    public $requests;
    function __construct($mylaps_id)
    {
        $this->mylaps_id = $mylaps_id;
        $this->requests = new \Requests_Session();
    }

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
     * @return SimpleXMLElement[]
     * @throws MyLapsException
     */
    function activities()
    {
        $response = self::get(sprintf('https://speedhive.mylaps.com/Practice/%1$d/PracticeTrackData?id=%1$d', $this->mylaps_id));
        $dom=new DOMDocument;
        @$dom->loadHtml($response->body);
        $xml=simplexml_import_dom($dom);
        $activities=$xml->xpath("/html/body//a[contains(@href,'Activity')]"); ///@href
        return $activities;
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
        $csv=str_replace(chr(0),'',$response->body);
        return $csv;
    }

    /**
     * @param $activityId
     * @return array
     * @throws MyLapsException
     */
    function activity_info($activityId)
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
        if(!empty($transponder_name))
            $transponder_name = trim($transponder_name[1]);
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
     * @param string $avatar_file Local file to save the avatar to
     * @throws MyLapsException
     */
    public static function download_avatar($avatar_url, $avatar_file)
    {
        self::get($avatar_url, [], ['filename'=>$avatar_file]);
        if(!file_exists($avatar_file))
            throw new MyLapsException('Avatar download error');
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