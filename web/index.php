<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Validator\Constraints as Assert;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/view',
    'twig.options' => array('debug' => true)
));
// $app['twig']->addExtension(new Twig_Extensions_Extension_Debug());

$app->match('/', function (Request $request) use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->match('/merci', function (Request $request) use ($app) {
    return $app['twig']->render('merci.html.twig');
});

$app->match('/questionnaire/{idPage}', function (Request $request, $idPage) use ($app) {

  $data  = Yaml::parse(file_get_contents(__DIR__ . '/../config/questions.yml'));
  $likertScales = $data['likertScales'];
  $likertQuestions = $data['likertQuestions'];

	$domains = array(
	  'Industrie',
	  'Agroalimentaire',
	);

    $sessionData = $app['session']->get('data');
	if(!$sessionData)  {
		$sessionData = array();
		if($idPage != 1)  {
			$nextPage = '/questionnaire/1';
			return $app->redirect($nextPage);
		}
	}

    $formBuilder = $app['form.factory']->createBuilder('form', $sessionData);
    if ($idPage <= count($likertQuestions)) {
      foreach( $likertQuestions['page'. $idPage] as $qKey => $likertQuestion) {
          $formBuilder->add( 'page'.$idPage.'_item'.$qKey , 'choice', array(
              'choices' => $likertScales[ $likertQuestion['scale'] ] ,
              'expanded' => true,
              'multiple' => false,
              'constraints' => new Assert\Choice(array_keys($likertScales[ $likertQuestion['scale'] ])),
              'attr' => array(
                'class' => $likertQuestion['scale']. ' likert',
              ),
              'label' => $likertQuestion['label'],
          ));
      }
     } else {
      $formBuilder
        ->add( 'Societe', 'text', array(
            'required' => false ,
        ))
        ->add( 'Domaine', 'choice', array(
            'choices' => $domains ,
            'expanded' => true,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($domains)),
        ))
      ;
    }
    $form = $formBuilder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $data = array_merge($sessionData, $formData);
	    $app['session']->set('data',$data);

            if ($idPage > count($likertQuestions)) {
              // Store data in CSV
              // Send mail with data
              $nextPage = '/merci';
            } else {
              $nextPage = '/questionnaire/'.($idPage+1);;
            }
            return $app->redirect($nextPage);
        }
    }

    // display the form
    return $app['twig']->render('form.html.twig', array('form' => $form->createView(), 'idPage' => $idPage));
})->convert('idPage', function ($id) { return (int) $id; });

$app->run();
