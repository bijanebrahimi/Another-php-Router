

Another PHP Router
===========================

Introduction
-----------------------------

it's another router class for php. read the rest of manual for the detailed features.

How to [Simply] Use it
-----------------------------

there are 3 entities that must be defined in Router class.

### 1. Patterns

a pattern defines how dynamic parts of a URL must look like. in fact, the patterns are essential to define dynamic routes. for example, the _post\_id_ and _published\_date_ in a url will be detected by three different patterns. 

    // Syntax
    Router::pattern($pattern_name, $pattern_regex);

**Example**: consider these two URL schema: [/news/2013/12/](http://example.com/news/2012/12) and [/post/162/](http://example.com/post/162). the first URL consists of two dynamic part: **Year** and **Month** and the second one consists of one, **id**. these dynamic parts are naturally different, so we need to define 3 different patterns to detect them properly.

    Router::pattern('year', '/^[1-2][0-9]{3}$/i');
    Router::pattern('month', '/^[0-9]{1,2}$/i');
    Router::pattern('id', '/^[1-9][0-9]+$/i');


### 2. defaults

a default value will be used for part of a URL when we don't want to mention every time we are defining a route or creating a like. 

    // Syntax
    Router::defaults($default_name, [$default_value[, $default_pattern]]);

**Example**: consider these URL schema: [http://www.examlpe.com:80/](http://www.examlpe.com:80/)
there are 5 different default value available. 

    Router::defaults('method', 'GET|POST');
    Router::defaults('protocol', 'http');
    Router::defaults('subdomain', 'www');
    Router::defaults('domain', 'example.com');
    Router::defaults('port', 80);

### 2. Routes

finally we need to define routes.

    // Syntax
    Router::route($route_name, $route_array, $route_value);

**Example**: consider defining routes for the same URL schema we mentioned before: [/news/2013/12/](http://example.com/news/2012/12) and [/post/162/](http://example.com/post/162).

    Router::route('archive', array('url'=>'/news/:year/:month'), array('class'=>'news', 'method'=>'archive'));
    Router::route('article', array('url'=>'/post/:id'), array('class'=>'posts', 'method'=>'view'));

**Explanation**: for the first route, we defined the URL consists of two dynamic part detectable by two patterns we defined earlier. any request like [http://www.example.com/news/2013/02](http://www.example.com/news/2013/02) will be matched. 

### Putting it all together

    //index.php
    // defining the patterns
    Router::pattern('year', '/^[0-9]{4}$/i');
    Router::pattern('month', '/^[0-9]{2}$/i');
    Router::pattern('id', '/^[1-9][0-9]+$/i');
    
    // defining the default values
    Router::defaults('method', 'GET|POST');
    Router::defaults('protocol', 'http');
    Router::defaults('subdomain', 'www');
    Router::defaults('domain', 'example.com');
    Router::defaults('port', 80);

    // defining the routes
    Router::route('archive', array('url'=>'/news/:year/:month'), array('class'=>'news', 'method'=>'archive'));
    Router::route('article', array('url'=>'/post/:id'), array('class'=>'posts', 'method'=>'view'));
    
    // match
    $result = Router::find();
    if(is_array($result)){
        $class_name = $result[0];
        $method_name = $result[1];
        $class_obj = new $class_name();
        if(method_exists($class_obj, $method_name)){
            $class_obj->$method_name();
        }
    }

    // news.php
    class news{
       public function archive(){
           $year = Router::get('year');
           $month = Router::get('month');
           // do something
       }
    }


Advanced Usage
-----------------------------

### override default values

it is possible to override default values for a specific route. for example, what if you have different subdomain for only media files or different protocol for high security routes? we can override any default value by defining them in $route_array parameter in route method.

    // overriding subdomain
    Router::route('image', array('url'=>'/img/:id', 'subdomain'=>'media'), array('class'=>'media', 'method'=>'image'));
    Router::route('login', array('url'=>'/login', 'protocol'=>'https'), array('class'=>'users', 'method'=>'logni'));

### set pattern for default values

you can define a default value with a pattern. in this case, if you want to override it in some routes, it should match the pattern given. 

    Router::pattern('protocol_pattern', '/https/i');
    Router::defaults('prorocol', 'https', 'protocol_pattern');

so if you want to override the protocol value in a route, it should match the pattern first. the following code fails because we strictly forced the protocol to not be http

    $login_link = Router::link('login', array('url'=>'/login', 'protocol'=>'http'), true);

### Override a default value/pattern in an individual route

we can also override a default's value/patterns in defining a route

    // set default protocol to https
    Router::route('login', array('url'=>'/login', 'protocol'=>array('https')), array('class'=>'users', 'method'=>'login'))
    // set default protocol to http but allows https if overridden 
    Router::pattern('https_pattern', '/https?/i');
    Router::route('setting', array('url'=>'/settings', 'protocol'=>array('http', :https_pattern)), array('class'=>'users', 'method'=>'login'))

### generating routes URL

you can generate a URL from a defined route by calling *link()* method.

    // Syntax
    Router::link($router_name, $route_parameters[, $fqdn=false]);

**Example**: consider the routes we defined earlier, *archive*, *article*, *login* and *image*. we can generate appropriate URL links for them to use in html forms.

    $article_link = Router::link('article', array(12));
    $archive_link = Router::link('archive', array(2013, 2));
    $same_archive_link = Router::link('archive', array('month'=>2, 'year'=>2013));
    $login_link = Router::link('login', array(), true);
    $image_link = Router::link('image', array(20), true);

    // the above code produce the following results
    $article_link == "/post/12";
    $archive_link == "/archive/2013/2";
    $same_archive_link == "/archive/2013/2";
    $login_link == "https://www.example.com/login";
    $image_link == "http://media.example.com/img/20"

**Note**: by overriding the default values, it is possible to create links pointing to other websites like CDNs.

    // using jQuery from CND
    // defining it's pattern and route
    Router::pattern('jquery', '/^jquery\-[0-9\.]+\.min\.js$/i');
    Router::route('jquery', array('url'=>'/:jquery', 'subdomain'=>'code', 'domain'=>'jquery.com'), array());

    // generating cnd link
    $jquery_link = Router::link('jquery', array('jquery-1.9.1.min.js'), true);

    // result
    $jquery_link == 'http://code.jquery.com/jquery-1.9.1.min.js'

### getting URL parameters

when requested URL matched with one of the defined routes, the dynamic parts of the URL will be accessible using Router::get() method as we mentioned before:

    // news.php
    class news{
        public function archive(){
            $year = Router::get('year');
            $month = Router::get('month');
        }
    }

we can also override the variable name these variables will be held in Router. 

    // index.php
    // defining routes
    Router::route('archive', array('url'=>'/archive/from:year/to:year'), array('class'=>'news', 'method'=>'ranged_archive'))

    // news.php
    class news{
        public function ranged_archive(){
            $from = Router::get('from');
            $to = Router::get('to');
        }
    }

### Custom route value

it's possible to define a custome value for routes. it can be an array containing any kinds of information inside.

**Example**: simple static HTML file as route value

    // index.php
    Router::route('home', array('url'=>'/'), array('public/index.html'));
    
    $result = Router::find();
    include($result[0]);
