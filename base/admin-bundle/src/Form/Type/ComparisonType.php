<?php

namespace DomBase\DomAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonType extends AbstractType
{
    public const EQ = '=';
    public const NEQ = '!=';
    public const GT = '>';
    public const GTE = '>=';
    public const LT = '<';
    public const LTE = '<=';
    public const CONTAINS = 'like';
    public const NOT_CONTAINS = 'not like';
    public const STARTS_WITH = 'like*';
    public const ENDS_WITH = '*like';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'type' => 'numeric',
            'choices' => static function (Options $options) {
                $choices = [];
                switch ($options['type']) {
                    case 'numeric':
                        $choices = [
                            'filter.label.is_equal_to' => self::EQ,
                            'filter.label.is_not_equal_to' => self::NEQ,
                            'filter.label.is_greater_than' => self::GT,
                            'filter.label.is_greater_than_or_equal_to' => self::GTE,
                            'filter.label.is_less_than' => self::LT,
                            'filter.label.is_less_than_or_equal_to' => self::LTE,
                        ];
                        break;
                    case 'text':
                        $choices = [
                            'filter.label.contains' => self::CONTAINS,
                            'filter.label.not_contains' => self::NOT_CONTAINS,
                            'filter.label.starts_with' => self::STARTS_WITH,
                            'filter.label.ends_with' => self::ENDS_WITH,
                            'filter.label.exactly' => self::EQ,
                            'filter.label.not_exactly' => self::NEQ,
                        ];
                        break;
                    case 'datetime':
                        $choices = [
                            'filter.label.is_same' => self::EQ,
                            'filter.label.is_not_same' => self::NEQ,
                            'filter.label.is_after' => self::GT,
                            'filter.label.is_after_or_same' => self::GTE,
                            'filter.label.is_before' => self::LT,
                            'filter.label.is_before_or_same' => self::LTE,
                        ];
                        break;
                    case 'array':
                        $choices = [
                            'filter.label.contains' => self::CONTAINS,
                            'filter.label.not_contains' => self::NOT_CONTAINS,
                        ];
                        break;
                    case 'entity':
                        $choices = [
                            'filter.label.is_same' => self::EQ,
                            'filter.label.is_not_same' => self::NEQ,
                        ];
                        break;
                }

                return $choices;
            },
            'translation_domain' => 'DomAdminBundle',
        ]);
        $resolver->setAllowedTypes('type', 'string');
        $resolver->setAllowedValues('type', ['array', 'datetime', 'entity', 'numeric', 'text']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
