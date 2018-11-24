<?php
namespace Handlers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class APIHandler
{
    public static $git_search_endpoint = 'https://api.github.com/search/repositories';

    //fetches repos based on language and keyword
    public function getGITRepos(string $keyword, $language) {
        $client = new Client();
        $params = array('q' => $keyword.' in:name',
                        'sort' => 'stars',
                        'order' => 'desc');
        $res = $client->get(self::$git_search_endpoint, ['query' => $params]);
        if ($res->getStatusCode() == 200){
          $response_json = json_decode($res->getBody());
          return $this->filterJSON($response_json);
        }
    }

    // sanotizes to only the attributes that we need
    private function filterJSON($response_json){
      $sanitized_results = [];
      foreach($response_json->items as $item){
        $sanitized_item = [
          'updated_at' => $item->updated_at,
          'description' => $item->description,
          'forks_count' => $item->forks_count,
          'html_url' => $item->html_url,
          'name' => $item->name,
          'stargazers_count' => $item->stargazers_count,
          'watchers_count' => $item->watchers_count,
          'owner' => $item->owner->login
        ];
        array_push($sanitized_results, $sanitized_item);
      }
      return $sanitized_results;
    }

    // check if there is a package.json and fetch it
    public function getPackageDotJSON($repo) {
        // var_dump($repo);
        $url = $repo['html_url'];

        // echo "RRR:: " . $repo;
        // echo "RR URL:: " . $repo['html_url'];
        // $this->ci->logger->info("repo data is:: " . var_dump($repo));
        $content_url = 'https://api.github.com/repos/'.str_replace('https://github.com/', '', $url).'/contents/package.json';

        // echo "\n Content URL:: " . $content_url;

        $client = new Client();

        // check for package.json
        try{
          $res = $client->get($content_url);
          $json_response = json_decode($res->getBody());
          if (isset($json_response->message)){
            // echo  "A:: \n";
            return ['error' => 'There is no package.json file in the project.'];
          }else{
            // echo  "B:: \n";
            $package_json_url = $json_response->download_url;
            // echo "package download url:: " . $package_json_url;
          }
        }catch (ClientException $e){
          // echo  "\n ERR " . var_export($e->getResponse()->getStatusCode());

          if ($e->getResponse()->getStatusCode() == 404){
            // echo "\n NO PACKAGE FILE FOUND:: ";
            return ['error' => 'There is no package.json file in the project.'];
          }
          else
            return ['error' => 'There is an error connecting to Github.'];
        }

        // get package.json
        try{
          $res = $client->get($package_json_url);
        }catch (ClientException $e){
          return ['error' => 'There is an error connecting to Github.'];
        }

        // parse package.json
        if ($res->getStatusCode() == 200){
          // echo "\nRES BODY:: " . var_dump($res->getBody());
          $response_json = json_decode($res->getBody());
          // echo "\nRES BODY JSON PARSED:: " . var_dump($response_json);
          $repository = $this->parseJSON($response_json);
          // echo "\nREPP:: " . var_dump($repository);
          $repository['url'] = $url;
          $repository['repo'] = $repo;
          // echo "\nREPPO :: " . var_dump($repository);
          return $repository;
        }else{
          return ['error' => 'There is an issue accessing Github.'];
        }
    }





    //Parse the JSON response to get dependencies and devDependencies
    private function parseJSON($response_json){
      // echo "\nRJJ:: " . var_dump($response_json);
      if(!isset($response_json->devDependencies) && !isset($response_json->dependencies)){
        return array('error' => 'The package.json has no dependencies.');
      }
      // echo "\n NN : " . $response_json->name;
      $repository = ['name' => $response_json->name];
      $dependencies = [];
      if(isset($response_json->devDependencies)){
        foreach($response_json->devDependencies as $dep => $versions){
          array_push($dependencies, $dep);
        }
      }
      if(isset($response_json->dependencies)) {
        foreach($response_json->dependencies as $dep => $versions){
          array_push($dependencies, $dep);
        }
      }
      // echo "\n DD : " . var_dump($dependencies);
      $repository['dependencies'] = $dependencies;
      // echo "\n ALL:: " . var_dump($repository);
      return $repository;
    }
}
 ?>
