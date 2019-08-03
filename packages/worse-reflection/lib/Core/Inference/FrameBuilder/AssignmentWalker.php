<?php

namespace Phpactor\WorseReflection\Core\Inference\FrameBuilder;

use Microsoft\PhpParser\Node;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\Expression\ListIntrinsicExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\SubscriptExpression;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Inference\FrameBuilder;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;
use Phpactor\WorseReflection\Core\Logger;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\Variable as WorseVariable;
use Microsoft\PhpParser\Token;
use Phpactor\WorseReflection\Core\Type;
use Microsoft\PhpParser\Node\ArrayElement;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;

class AssignmentWalker extends AbstractWalker
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function canWalk(Node $node): bool
    {
        return $node instanceof AssignmentExpression;
    }

    public function walk(FrameBuilder $builder, Frame $frame, Node $node): Frame
    {
        assert($node instanceof AssignmentExpression);

        $rightContext = $builder->resolveNode($frame, $node->rightOperand);

        if ($this->hasMissingTokens($node)) {
            return $frame;
        }

        if ($node->leftOperand instanceof Variable) {
            return $this->walkParserVariable($frame, $node->leftOperand, $rightContext);
        }

        if ($node->leftOperand instanceof ListIntrinsicExpression) {
            return $this->walkList($frame, $node->leftOperand, $rightContext);
        }

        if ($node->leftOperand instanceof MemberAccessExpression) {
            return $this->walkMemberAccessExpression($builder, $frame, $node->leftOperand, $rightContext);
        }

        if ($node->leftOperand instanceof SubscriptExpression) {
            return $this->walkSubscriptExpression($builder, $frame, $node->leftOperand, $rightContext);
        }


        $this->logger->warning(sprintf(
            'Do not know how to assign to left operand "%s"',
            get_class($node->leftOperand)
        ));

        return $frame;
    }

    private function walkParserVariable(Frame $frame, Variable $leftOperand, SymbolContext $rightContext)
    {
        $name = $leftOperand->name->getText($leftOperand->getFileContents());
        $context = $this->symbolFactory()->context(
            $name,
            $leftOperand->getStart(),
            $leftOperand->getEndPosition(),
            [
                'symbol_type' => Symbol::VARIABLE,
                'type' => $rightContext->type(),
                'value' => $rightContext->value(),
            ]
        );

        $frame->locals()->add(WorseVariable::fromSymbolContext($context));

        return $frame;
    }

    private function walkMemberAccessExpression(
        FrameBuilder $builder,
        Frame $frame,
        MemberAccessExpression $leftOperand,
        SymbolContext $typeContext
    ): Frame {
        $variable = $leftOperand->dereferencableExpression;

        // we do not track assignments to other classes.
        if (false === in_array($variable, [ '$this', 'self' ])) {
            return $frame;
        }

        $memberNameNode = $leftOperand->memberName;

        // TODO: Sort out this mess.
        //       If the node is not a token (e.g. it is a variable) then
        //       evaluate the variable (e.g. $this->$foobar);
        if ($memberNameNode instanceof Token) {
            $memberName = $memberNameNode->getText($leftOperand->getFileContents());
        } else {
            $memberNameInfo = $builder->resolveNode($frame, $memberNameNode);

            if (false === is_string($memberNameInfo->value())) {
                return $frame;
            }

            $memberName = $memberNameInfo->value();
        }

        $context = $this->symbolFactory()->context(
            $memberName,
            $leftOperand->getStart(),
            $leftOperand->getEndPosition(),
            [
                'symbol_type' => Symbol::VARIABLE,
                'type' => $typeContext->type(),
                'value' => $typeContext->value(),
            ]
        );

        $frame->properties()->add(WorseVariable::fromSymbolContext($context));

        return $frame;
    }

    private function walkList(Frame $frame, ListIntrinsicExpression $leftOperand, SymbolContext $symbolContext): Frame
    {
        $value = $symbolContext->value();

        foreach ($leftOperand->listElements as $elements) {
            foreach ($elements as $index => $element) {
                if (!$element instanceof ArrayElement) {
                    continue;
                }

                $elementValue = $element->elementValue;

                if (!$elementValue instanceof Variable) {
                    continue;
                }

                if (null === $elementValue || null === $elementValue->name) {
                    continue;
                }

                $varName = $elementValue->name->getText($leftOperand->getFileContents());
                $variableContext = $this->symbolFactory()->context(
                    $varName,
                    $element->getStart(),
                    $element->getEndPosition(),
                    [
                        'symbol_type' => Symbol::VARIABLE,
                    ]
                );

                if (is_array($value) && isset($value[$index])) {
                    $variableContext = $variableContext->withValue($value[$index]);
                    $variableContext = $variableContext->withType(Type::fromString(gettype($value[$index])));
                }

                $frame->locals()->add(WorseVariable::fromSymbolContext($variableContext));
            }
        }

        return $frame;
    }

    private function walkSubscriptExpression(FrameBuilder $builder, Frame $frame, SubscriptExpression $leftOperand, SymbolContext $rightContext): Frame
    {
        if ($leftOperand->postfixExpression instanceof MemberAccessExpression) {
            $rightContext = $rightContext->withType(Type::array());
            $this->walkMemberAccessExpression($builder, $frame, $leftOperand->postfixExpression, $rightContext);
        }

        return $frame;
    }

    private function hasMissingTokens(AssignmentExpression $node)
    {
        // this would probably never happen ...
        if (false === $node->parent instanceof ExpressionStatement) {
            return false;
        }

        foreach ($node->parent->getDescendantTokens() as $token) {
            if ($token instanceof MissingToken) {
                return true;
            }
        }
        
        return false;
    }
}
