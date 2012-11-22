<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Validator\Constraints as Assert;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;

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

	$likertScales = array(
	    'ok4' => array( 'Pas du tout d\'accord', 'Pas d\'accord', 'D\'accord', 'Tout à fait d\'accord'),
      'ok7' => array( 'Rien', 'Rare', 'Peu', 'Moyen', 'Courant', 'Frequent', 'Omniprésent'),
	);

	$likertQuestions = array(
	    array(
  		  array ('Ce programme est-il bien fait ?', 'ok4'),
  		  array ('Page 1 question 2', 'ok4'),
        array ('Ce programme est-il vraiment bien fait ?', 'ok7'),
        array ('Page 1 question 4', 'ok7'),
        array ('Page 1 question 5', 'ok4'),
      ),
	    array(
        array ('Ce programme est-il bien fait 2 ?', 'ok4'),
        array ('Page 2 question 2', 'ok4'),
	    ),
	);

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
      foreach( $likertQuestions[$idPage - 1] as $qKey => $likertQuestion) {
          $formBuilder->add( 'page'.$idPage.'_item'.$qKey , 'choice', array(
              'choices' => $likertScales[ $likertQuestion[1] ] ,
              'expanded' => true,
              'multiple' => false,
              'constraints' => new Assert\Choice(array_keys($likertScales[ $likertQuestion[1] ])),
              'attr' => array(
                'class' => $likertQuestion[1],
              ),
		          'label' => $likertQuestion[0],
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
    return $app['twig']->render('form.html.twig', array('form' => $form->createView()));
})->convert('idPage', function ($id) { return (int) $id; });

$app->run();
