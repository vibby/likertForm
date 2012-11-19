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
    'twig.path' => __DIR__,
));
    
$app->match('/', function (Request $request) use ($app) {
    return $app['twig']->render('index.html.twig');
});
    
$app->match('/merci', function (Request $request) use ($app) {
    return $app['twig']->render('merci.html.twig');
});

$app->match('/questionnaire/{idPage}', function (Request $request, $idPage) use ($app) {

	$likertScales = array(
	    '4ok' => array( 'Pas du tout d\'accord', 'Pas d\'accord', 'D\'accord', 'Tout à fait d\'accord'),
	);

	$likertQuestions = array(
	    array(
		array ('Ce programme est-il bien fait ?', '4ok'),
		array ('Page 1 question 2', '4ok'),
	    ),
	    array(
		array ('Ce programme est-il bien fait 2 ?', '4ok'),
		array ('Page 2 question 2', '4ok'),
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
		'label' => $likertQuestion[0],
          ));
      }
     } else {
      $formBuilder
        ->add( 'Societe', 'text')
        ->add( 'Domaine', 'choice', array(
            'choices' => $domains ,
            'expanded' => true,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($domains)),
        ));
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
