<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\TokenKind;

use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\TraitImport\TraitImport;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\TraitImport\TraitImports;
use Phpactor\WorseReflection\Core\Reflection\Collection\ChainReflectionMemberCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionConstantCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionInterfaceCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionMethodCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionPropertyCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionTraitCollection;

use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionConstantCollection as CoreReflectionConstantCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionInterfaceCollection as CoreReflectionInterfaceCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMethodCollection as CoreReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionPropertyCollection as CoreReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionTraitCollection as CoreReflectionTraitCollection;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Exception\ClassNotFound;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass as CoreReflectionClass;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionMethod;
use Phpactor\WorseReflection\Core\Visibility;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionTrait;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMemberCollection;

class ReflectionClass extends AbstractReflectionClass implements CoreReflectionClass
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var ClassDeclaration
     */
    private $node;

    /**
     * @var SourceCode
     */
    private $sourceCode;

    /**
     * @var ReflectionInterfaceCollection<ReflectionInterface>
     */
    private $interfaces;

    /**
     * @var ReflectionClassLike|null
     */
    private $parent;

    /**
     * @var ReflectionMethodCollection|null
     */
    private $methods;

    public function __construct(
        ServiceLocator $serviceLocator,
        SourceCode $sourceCode,
        ClassDeclaration $node
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->node = $node;
        $this->sourceCode = $sourceCode;
    }

    protected function node(): Node
    {
        return $this->node;
    }

    public function isAbstract(): bool
    {
        if (false === $this->node instanceof ClassDeclaration) {
            return false;
        }

        $modifier = $this->node->abstractOrFinalModifier;

        if (!$modifier) {
            return false;
        }

        return $modifier->kind === TokenKind::AbstractKeyword;
    }

    public function members(): ReflectionMemberCollection
    {
        return ChainReflectionMemberCollection::fromCollections([
            $this->constants(),
            $this->properties(),
            $this->methods()
        ]);
    }

    public function constants(): CoreReflectionConstantCollection
    {
        $parentConstants = null;
        if ($this->parent()) {
            $parentConstants = $this->parent()->constants();
        }

        $constants = ReflectionConstantCollection::fromClassDeclaration($this->serviceLocator, $this->node, $this);

        if ($parentConstants) {
            return $parentConstants->merge($constants);
        }

        foreach ($this->interfaces() as $interface) {
            $constants = $constants->merge($interface->constants());
        }

        return $constants;
    }

    public function parent()
    {
        if ($this->parent) {
            return $this->parent;
        }

        if (!$this->node->classBaseClause) {
            return;
        }

        // incomplete class
        if (!$this->node->classBaseClause->baseClass) {
            return;
        }

        try {
            $reflectedClass = $this->serviceLocator->reflector()->reflectClassLike(
                ClassName::fromString((string) $this->node->classBaseClause->baseClass->getResolvedName())
            );

            if (!$reflectedClass instanceof CoreReflectionClass) {
                $this->serviceLocator->logger()->warning(sprintf(
                    'Class cannot extend interface. Class "%s" extends interface or trait "%s"',
                    $this->name(),
                    $reflectedClass->name()
                ));
                return;
            }

            $this->parent = $reflectedClass;

            return $reflectedClass;
        } catch (ClassNotFound $e) {
        }
    }

    public function properties(): CoreReflectionPropertyCollection
    {
        $properties = ReflectionPropertyCollection::empty($this->serviceLocator);

        if ($this->traits()->count() > 0) {
            foreach ($this->traits() as $trait) {
                $properties = $properties->merge($trait->properties());
            }
        }

        if ($this->parent()) {
            $properties = $properties->merge(
                $this->parent()->properties()->byVisibilities([ Visibility::public(), Visibility::protected() ])
            );
        }

        $properties = $properties->merge(ReflectionPropertyCollection::fromClassDeclaration($this->serviceLocator, $this->node, $this));

        return $properties;
    }

    public function methods(CoreReflectionClass $contextClass = null): CoreReflectionMethodCollection
    {
        $cacheKey = $contextClass ? (string) $contextClass->name() : '*_null_*';

        if (isset($this->methods[$cacheKey])) {
            return $this->methods[$cacheKey];
        }

        $contextClass = $contextClass ?: $this;
        $methods = ReflectionMethodCollection::empty($this->serviceLocator);

        $traitImports = new TraitImports($this->node);

        /** @var TraitImport $traitImport */
        foreach ($traitImports as $traitImport) {
            $trait = $this->traits()->get($traitImport->name());

            $traitMethods = [];
            foreach ($trait->methods($contextClass) as $method) {
                if (false === $traitImport->hasAliasFor($method->name())) {
                    $traitMethods[] = $method;
                    continue;
                }

                $traitAlias = $traitImport->getAlias($method->name());
                $virtualMethod = VirtualReflectionMethod::fromReflectionMethod($trait->methods()->get($traitAlias->originalName()))
                    ->withName($traitAlias->newName())
                    ->withVisibility($traitAlias->visiblity($method->visibility()));

                $traitMethods[] = $virtualMethod;
            }
            $methods = $methods->merge(VirtualReflectionMethodCollection::fromReflectionMethods($traitMethods));
        }

        if ($this->parent()) {
            $methods = $methods->merge(
                $this->parent()->methods($contextClass)->byVisibilities([ Visibility::public(), Visibility::protected() ])
            );
        }

        $methods = $methods->merge(
            ReflectionMethodCollection::fromClassDeclaration(
                $this->serviceLocator,
                $this->node,
                $contextClass
            )
        );

        $this->methods[$cacheKey] = $methods;

        return $methods;
    }

    public function interfaces(): CoreReflectionInterfaceCollection
    {
        if ($this->interfaces) {
            return $this->interfaces;
        }

        $parentInterfaces = null;
        if ($this->parent()) {
            $parentInterfaces = $this->parent()->interfaces();
        }

        $interfaces = ReflectionInterfaceCollection::fromClassDeclaration($this->serviceLocator, $this->node);

        if ($parentInterfaces) {
            $interfaces = $parentInterfaces->merge($interfaces);
        }

        $this->interfaces = $interfaces;

        return $interfaces;
    }

    /**
     * @return CoreReflectionTraitCollection<ReflectionTrait>
     */
    public function traits(): CoreReflectionTraitCollection
    {
        $parentTraits = null;

        if ($this->parent()) {
            $parentTraits = $this->parent()->traits();
        }

        $traits = ReflectionTraitCollection::fromClassDeclaration($this->serviceLocator, $this->node);

        if ($parentTraits) {
            return $parentTraits->merge($traits);
        }

        return $traits;
    }


    public function memberListPosition(): Position
    {
        return Position::fromFullStartStartAndEnd(
            $this->node->classMembers->openBrace->fullStart,
            $this->node->classMembers->openBrace->start,
            $this->node->classMembers->openBrace->start + $this->node->classMembers->openBrace->length
        );
    }

    public function name(): ClassName
    {
        return ClassName::fromString((string) $this->node->getNamespacedName());
    }

    public function isInstanceOf(ClassName $className): bool
    {
        if ($className == $this->name()) {
            return true;
        }

        if ($this->parent()) {
            if ($this->parent()->isInstanceOf($className)) {
                return true;
            }
        }

        return $this->interfaces()->has((string) $className);
    }

    public function sourceCode(): SourceCode
    {
        return $this->sourceCode;
    }

    public function isConcrete(): bool
    {
        if (false === $this->isClass()) {
            return false;
        }

        return false === $this->isAbstract();
    }

    public function docblock(): DocBlock
    {
        return $this->serviceLocator->docblockFactory()->create($this->node()->getLeadingCommentAndWhitespaceText());
    }
}
