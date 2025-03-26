<a name="readme-top"></a>

<!-- PROJECT SHIELDS -->
[![Latest Stable Version](https://poser.pugx.org/orb/orb/v)](//packagist.org/packages/orb/orb)
[![License](https://poser.pugx.org/orb/orb/license)](//packagist.org/packages/orb/orb)

<!-- PROJECT LOGO -->
<div align="center">
    <h3 align="center">Orb</h3>

  <p align="center">
    A simple and fast framework to build REST APIs and simple web applications.
    <!--
    <br />
    <a href="https://github.com/othneildrew/Best-README-Template"><strong>Explore the docs »</strong></a>
    -->
  </p>
</div>

<!-- TABLE OF CONTENTS -->
<br />
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#testing">Testing</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#acknowledgments">Acknowledgments</a></li>
  </ol>
</details>

<!-- ABOUT THE PROJECT -->

## About The Project

Quickly and easily create REST APIs and simple web applications with Orb.  
Versatile enough to build any kind of application, and simple enough to be used by beginners.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- GETTING STARTED -->
## Getting Started

### Prerequisites

You need `PHP >= 8.2` to use Orb but the latest stable version of PHP is always recommended.

### Installation

Via [composer](https://getcomposer.org/) :

`composer require orb/orb`

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- USAGE EXAMPLES -->
## Usage

```php
require_once __DIR__.'/vendor/autoload.php';

use Orb\Orb;

$orb = new Orb();

$orb->get('/', 'Hello world !');
$orb->get('/using-function', fn() => 'Hello world !');
$orb->get('/using-invoke', MyController::class);
$orb->get('/using-class-method-1', 'MyController::method1');
$orb->get('/using-class-method-2', 'MyController->method2');
$orb->get('/psr7-response', fn() => new \Laminas\Diactoros\Response\HtmlResponse('Hello world !'));

$orb->run();
```

It is possible to return mostly anything from a route handler.  
If you do not return a PSR-7 `ResponseInterface`, Orb will try to convert the returned value to a `ResponseInterface`.

Note that Orb will return a JSON response if the returned value is an array or an object, and an XML response if the
returned value is a `SimpleXMLElement` or `DOMDocument`.  
The default response is a `text/html` response.


<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- Testing -->
## Testing

This package uses `Pest` as test framework.  
To run tests :

```shell
./vendor/bin/pest tests
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any
contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also
simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- LICENSE -->
## License

Distributed under the MIT License. See [License File](https://github.com/debuss/orb/blob/master/LICENSE.md) for more information.

<p align="right">(<a href="#readme-top">back to top</a>)</p>