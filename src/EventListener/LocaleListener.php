<?php

namespace App\EventListener;

use App\Enum\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Negotiation\LanguageNegotiator;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class LocaleListener
 *
 * @package App\EventListener
 */
class LocaleListener
{
    /**
     * @var string
     */
    private $defaultLocale;

    private $em;

    /**
     * LocaleListener constructor.
     * @param string $defaultLocale
     */
    public function __construct(string $defaultLocale, EntityManagerInterface $em)
    {
        $this->defaultLocale = $defaultLocale;
        $this->em = $em;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            $negotiator = new LanguageNegotiator();
            $header = $event->getRequest()->headers->get('Accept-Language');
            
            if (null !== $header) {
                $best = $negotiator->getBest(
                    $event->getRequest()->headers->get('Accept-Language'),
                    LocaleEnum::LOCALES
                );
    
                if (null !== $best) {
                    $request->getSession()->set('_locale', $best->getType());
                    $request->setLocale($request->getSession()->get('_locale', $best->getType()));
                }    
            }

            return;
        }

        if ($request->query->has('_locale')) {
            $locale = $request->query->get('_locale');
            if (\in_array($locale, LocaleEnum::LOCALES, false)) {
                $request->getSession()->set('_locale', $locale);
                $request->setLocale($request->getSession()->get('_locale', $locale));
            }
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }
}
