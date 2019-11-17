<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\TokenKind;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Inference\FrameBuilder;
use Phpactor\WorseReflection\Core\NodeText;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod as CoreReflectionMethod;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Visibility;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionParameterCollection as CoreReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Reflection\TypeResolver\MethodTypeResolver;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\TypeResolver\DeclaredMemberTypeResolver;
use Microsoft\PhpParser\NamespacedNameInterface;

class ReflectionMethod extends AbstractReflectionClassMember implements CoreReflectionMethod
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var MethodDeclaration
     */
    private $node;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var FrameBuilder
     */
    private $frameBuilder;

    /**
     * @var AbstractReflectionClass
     */
    private $class;

    /**
     * @var MethodTypeResolver
     */
    private $returnTypeResolver;

    /**
     * @var DeclaredMemberTypeResolver
     */
    private $memberTypeResolver;

    public function __construct(
        ServiceLocator $serviceLocator,
        AbstractReflectionClass $class,
        MethodDeclaration $node
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->node = $node;
        $this->class = $class;
        $this->returnTypeResolver = new MethodTypeResolver($this, $serviceLocator->logger());
        $this->memberTypeResolver = new DeclaredMemberTypeResolver();
    }

    public function name(): string
    {
        return $this->node->getName();
    }

    public function declaringClass(): ReflectionClassLike
    {
        $classDeclaration = $this->node->getFirstAncestor(ClassLike::class);

        assert($classDeclaration instanceof NamespacedNameInterface);
        $class = $classDeclaration->getNamespacedName();


        if (null === $class) {
            throw new \InvalidArgumentException(sprintf(
                'Could not locate class-like ancestor node for method "%s"',
                $this->name()
            ));
        }

        return $this->serviceLocator->reflector()->reflectClassLike(ClassName::fromString($class));
    }

    public function parameters(): CoreReflectionParameterCollection
    {
        return ReflectionParameterCollection::fromMethodDeclaration($this->serviceLocator, $this->node, $this);
    }

    /**
     * @deprecated use inferredTypes()
     */
    public function inferredReturnTypes(): Types
    {
        return $this->inferredTypes();
    }

    public function inferredTypes(): Types
    {
        return $this->returnTypeResolver->resolve();
    }

    /**
     * @deprecated use type()
     */
    public function returnType(): Type
    {
        return $this->type();
    }

    public function type(): Type
    {
        return $this->memberTypeResolver->resolve($this->node, $this->node->returnType, $this->class()->name());
    }

    public function body(): NodeText
    {
        $statements = $this->node->compoundStatementOrSemicolon->statements;
        return NodeText::fromString(implode(PHP_EOL, array_reduce($statements, function ($acc, $statement) {
            $acc[] = (string) $statement->getText();
            return $acc;
        }, [])));
    }

    public function class(): ReflectionClassLike
    {
        return $this->class;
    }

    protected function node(): Node
    {
        return $this->node;
    }

    protected function serviceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }

    public function isStatic(): bool
    {
        return $this->node->isStatic();
    }

    public function isAbstract(): bool
    {
        foreach ($this->node->modifiers as $token) {
            if ($token->kind === TokenKind::AbstractKeyword) {
                return true;
            }
        }

        return false;
    }

    public function isVirtual(): bool
    {
        return false;
    }
}
