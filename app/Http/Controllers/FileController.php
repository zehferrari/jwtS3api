<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use GenTux\Jwt\GetsJwtToken;
use Aws\Laravel\AwsFacade as AWS;
use Aws\Laravel\AwsServiceProvider;
use Log;

class FileController extends Controller
{
  use GetsJwtToken;

  /**
   * AWS S3 client
   * @var object
   */
  private $s3Client;

  /**
   * User authentication
   * @var object
   */
  private $auth;

  /**
   * FileController constructor.
   */
  public function __construct() {
    $this->auth = $this->jwtToken();
    $this->auth->validate();
    $this->s3Client = AWS::createClient('s3');
  }

  /**
   * Display a listing of the users resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $reply = [];
    $iterator = $this->s3Client->getIterator('ListObjects', array(
        'Bucket' => getenv('AWS_BUCKET'),
        'prefix' => 'users/'.$this->getUserID().'/'
    ));

    foreach ($iterator as $object) {
      $result = $this->s3Client->getObject(array(
          'Bucket' => getenv('AWS_BUCKET'),
          'Key' => $object['Key']
      ));
      if (isset($result['Metadata']) and isset($result['Metadata']['author'])) {
        $reply[] = $result['Metadata'];
      }
    }
    return $reply;
  }
  
  private function getUserID() {
    $payload = $this->jwtPayload();
    if (isset($payload['d'])) {
      $value = $payload['d'];
      if (isset($value['uid'])) {
        return $value['uid'];
      }
    }
    return null;
  }

  private function createObjID() {
    return str_replace(array('.', ' '), '', microtime());
  }

  private function getPath($key) {
    return "users/".$this->getUserID()."/".$key;
  }

  private function getURL($key) {
    $url = getenv('FIREBASE').'/files/'.$this->getUserID().'/'.$key.'.json?auth='.getenv('JWT_SECRET');
    return $url;
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function upload(Request $request)
  {
    $response = [];
    $source = $request->file();
    foreach ($source as $file) {
      $key = $this->createObjID();
      $upload = [
          'Bucket'        => getenv('AWS_BUCKET'),
          'Key'           => $this->getPath($key),
          'SourceFile'    => $file->getRealPath(),
          'ContentType'   => $file->getMimeType(),
          'Metadata'      => [
              'key'       => $key,
              'name'      => $file->getClientOriginalName(),
              'mime'      => $file->getMimeType(),
              'author'    => $this->getUserID(),
              'size'      => $file->getSize()
//                    ,'preview' => 'get api response to update this information'
          ]
      ];
      if (count($source)==1 && $request->input('metadata')) {
        $obj = $request->input('metadata');
        foreach(['title', 'type', 'description'] as $field) {
          if (isset($obj[$field]) and strlen($obj[$field])) {
            $upload['Metadata'][$field] = $obj[$field];
          }
        }
      }
      $response[] = $upload['Metadata'];

      // Upload file to AWS S3
      $this->s3Client->putObject($upload);
      
      $fbObj = $upload['Metadata'];
      unset($fbObj['key'], $fbObj['author']);

      // File manager - Add Firebase record
      $http_client = new \GuzzleHttp\Client();
      $http_client->request('PUT', $this->getURL($key), [
          'json' => $fbObj
      ]);

    }
    return $response;
  }

  /**
   * Display the specified resource.
   *
   * @param  string $path
   * @return \Illuminate\Http\Response
   */
  public function download($key)
  {
    $command = $this->s3Client->getCommand('GetObject', [
        'Bucket' => getenv('AWS_BUCKET'),
        'Key' => $key
    ]);

    $request = $this->s3Client->createPresignedRequest($command, '+20 minutes');
    return (string) $request->getUri();
  }

  /**
   * Storage file preview thumbnail on S3
   *
   * @param  string
   * @return string
   */
  public function preview()
  {
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  string $path
   * @return \Illuminate\Http\Response
   */
  public function destroy($path)
  {
    // Remove S3 object path
  }
}
