<?php

namespace DomBase\DomAdminBundle\Exception;

use Symfony\Component\Debug\Exception\FlattenException as BaseFlattenException;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class FlattenException extends BaseFlattenException
{
    /** @var ExceptionContext */
    private $context;

    /**
     * @param \Exception $exception
     * @param int        $statusCode
     * @param array      $headers
     *
     * @return FlattenException
     *
     * @throws \RuntimeException
     */
    public static function create(\Exception $exception, $statusCode = null, array $headers = [])
    {
        if (!$exception instanceof BaseException) {
            throw new \RuntimeException(\sprintf('You should only try to create an instance of "%s" with a "DomBase\DomAdminBundle\Exception\BaseException" instance, or subclass. "%s" given.', __CLASS__, \get_class($exception)));
        }

        /** @var FlattenException $e */
        $e = parent::create($exception, $statusCode, $headers);
        $e->context = $exception->getContext();

        return $e;
    }

    public function getPublicMessage()
    {
        return $this->context->getPublicMessage();
    }

    public function getDebugMessage()
    {
        return $this->context->getDebugMessage();
    }

    public function getParameters()
    {
        return $this->context->getParameters();
    }

    public function getTranslationParameters()
    {
        return $this->context->getTranslationParameters();
    }

    public function getStatusCode()
    {
        return $this->context->getStatusCode();
    }
}
