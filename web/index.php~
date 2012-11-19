<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Validator\Constraints as Assert;
use Silex\Provider\FormServiceProvider;

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
    
$likertScales = array(
    '4ok' => array( 'Pas du tout d\'accord', 'Pas d\'accord', 'D\'accord', 'Tout à fait d\'accord'),
    )
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

$app->match('/', function (Request $request) use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->match('/questionnaire/{idPage}', function (Request $request, $idPage) use ($app) {
    $sessionData = $app['session']->get('data');             

    $formBuilder = $app['form.factory']->createBuilder('form', $sessionData);
    if ($idPage < count($likertQuestions)) {
      foreach( $likertQuestions[$idPage - 1] as $likertQuestion) {
          $formBuilder->add( $likertQuestion[0] , 'choice', array(
              'choices' => $likertScales[ $likertQuestion[1] ] ,
              'expanded' => true,
              'multiple' => false,
              'constraints' => new Assert\Choice(array_keys($likertScales[ $likertQuestion[1] ])),
          ));
      }
     } else {
      $formBuilder
        ->add( 'Société', 'text')
        ->add( 'Domaine', 'text', 'choice', array(
            'choices' => $domains ,
            'expanded' => true,
            'multiple' => false,
            'constraints' => new Assert\Choice(array_keys($domains)),
        ))
    }
    $form = $formBuilder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $data = array_merge($sessionData, $formData);

            // redirect somewhere
            if ($idPage > count($likertQuestions)) {
              // Store data in CSV
              // Send mail with data
              $nextPage = '/merci';
            } else {
              $app['session']->set('data',$data);  
              $nextPage = '/questionnaire/'.$idPage++;
            }
            return $app->redirect($nextPage);
        }
    }

    // display the form
    return $app['twig']->render('form.html.twig', array('form' => $form->createView()));
})->convert('idPage', function ($id) { return (int) $id; });

$app->run();