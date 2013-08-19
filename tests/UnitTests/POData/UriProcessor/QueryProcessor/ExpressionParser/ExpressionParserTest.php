<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\String;
use POData\Providers\Metadata\Type\Navigation;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Null1;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser;
use POData\Common\ODataException;
use POData\Providers\Metadata\IDataServiceMetadataProvider;

use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var IDataServiceMetadataProvider  */
    private $_northWindMetadata;
    
    protected function setUp()
    {        
        $this->_northWindMetadata = NorthWindMetadata::Create();
    }

    public function testConstantExpression()
    {
        $expression = '123';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);
        $this->AssertEquals($expr->getValue(), 123);

        $expression = '-127';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);
        $this->AssertEquals($expr->getValue(), -127);

        $expression = '125L';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int64, true);
        $this->AssertEquals($expr->getValue(), '125');

        $expression = '122.3';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getValue(), 122.3);

        $expression = '126E2';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getValue(), '126E2');

        $expression = '121D';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getValue(), '121');

        $expression = '126.3F';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Single, true);
        $this->AssertEquals($expr->getValue(), '126.3');

        $expression = '126.3M';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Decimal, true);
        $this->AssertEquals($expr->getValue(), '126.3');

        $expression = '126E2m';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof Decimal, true);
        $this->AssertEquals($expr->getValue(), '126E2');

        $expression = 'datetime\'1990-12-23\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getType() instanceof DateTime, true);
        $this->AssertEquals($expr->getValue(), '\'1990-12-23\'');

        $expression = 'datetime\'11990-12-23\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'dateime\' validation has not been raised');
        }
        catch(ODataException $ex)
        {
            $this->AssertEquals('Unrecognized \'Edm.DateTime\' literal \'datetime\'11990-12-23\'\' in position \'0\'.', $ex->getMessage());
        }


        //$expression = 'binary\'ABCD\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->AssertEquals($expr instanceof ConstantExpression, true);
        //$this->AssertEquals($expr->getType() instanceof Binary, true);

        //$expression = 'X\'123F\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->AssertEquals($expr instanceof ConstantExpression, true);
        //$this->AssertEquals($expr->getType() instanceof Binary, true);
            
    }
    
    public function testPropertyAccessExpression()
    {
        $expression = 'CustomerID';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getType() instanceof String, true);

        $expression = 'Rating';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);

        $expression = 'Address';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getType() instanceof Navigation, true);
        $this->AssertEquals($expr->getResourceType()->getFullName(), 'Address');

        $expression = 'Address/LineNumber';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);
        $this->AssertEquals($expr->getResourceType()->getFullName(), 'Edm.Int32');
        $this->AssertEquals($expr->getParent()->getResourceType()->getFullName(), 'Address');

        $expression = 'Address\LineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'invalid chatacter\' has not been thrown');
        } catch(ODataException $exception) {
            $this->AssertEquals('Invalid character \'\\\' at position 7', $exception->getMessage());
        }

        $expression = 'CustomerID1';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch(ODataException $exception) {
           $this->AssertEquals('No property \'CustomerID1\' exists in type \'Customer\' at position 0', $exception->getMessage());
        }

        $expression = 'Address/InvalidLineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch(ODataException $exception) {
           $this->AssertEquals('No property \'InvalidLineNumber\' exists in type \'Address\' at position 8', $exception->getMessage());
        }

        $expression = 'Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch(ODataException $exception) {
           $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }

        $expression = 'Customer/CustomerID';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getType() instanceof String, true);
        $this->AssertEquals($expr->getResourceType()->getFullName(), 'Edm.String');

        $expression = 'Customer/Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch(ODataException $exception) {
           $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }


    }

    public function testArithmeticExpressionAndOperandPromotion()
    {
        $expression = "1 add 2";
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getValue(), 1);

        $expression = "1 sub 2.5";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getType() instanceof Double, true);
        $this->AssertEquals($expr->getRight()->getType() instanceof Double, true);

        $expression = "1.1F sub 2";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getType() instanceof Single, true);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getType() instanceof Single, true);
        $this->AssertEquals($expr->getRight()->getType() instanceof Single, true);

        $expression = "1.1F mul 2.7";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getType() instanceof Double, true);
        $this->AssertEquals($expr->getRight()->getType() instanceof Double, true);

        $expression = "1 add 2 sub 4";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr->getNodeType(), ExpressionType::SUBTRACT);
        $this->AssertEquals($expr->getLeft() instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getNodeType(), ExpressionType::ADD);
        $this->AssertEquals($expr->getRight()->getNodeType(), ExpressionType::CONSTANT);
        $this->AssertEquals($expr->getLeft()->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getLeft()->getValue(), 1);

        $expression = "1 add (2 sub 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getRight()->getNodeType(), ExpressionType::SUBTRACT);
        $this->AssertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->AssertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getLeft()->getValue(), 2);

        $expression = "1 add (2 sub 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getRight()->getNodeType(), ExpressionType::SUBTRACT);
        $this->AssertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->AssertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getLeft()->getValue(), 2);

        $expression = "1 add 2 mul 4";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getRight()->getNodeType(), ExpressionType::MULTIPLY);
        $this->AssertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->AssertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getRight()->getValue(), 4);

        $expression = "Rating add 2.5 mul 3.4F";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getType() instanceof Double, true);
        $this->AssertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->AssertEquals($expr->getLeft() instanceof PropertyAccessExpression, true);
        $this->AssertEquals($expr->getLeft()->getType() instanceof Double, true);

        $expression = "5.2 mul true";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Double and Boolean was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'mul\' incompatible with operand types Edm.Double and Edm.Boolean', $exception->getMessage());
        }


        $expression = "1F add 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "1 add 'MyString'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Int32 and String was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.String', $exception->getMessage());
        }


        $expression = "1F add 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "datetime'1990-12-12' add datetime'1991-11-11'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between DateTime types was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.DateTime and Edm.DateTime', $exception->getMessage());
        }


    }

    public function testRelationalExpression()
    {
        $expression = '2.5 gt 2';
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getType() instanceof Boolean, true);

        $expression = 'true le false';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getType() instanceof Boolean, true);
        $this->AssertEquals($expr->getLeft() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getLeft()->getValue(), 'true');
        $this->AssertEquals($expr->getRight()->getType() instanceof Boolean, true);

        $expression = 'Country eq null';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof FunctionCallExpression, true);
        $this->AssertEquals($expr->getFunctionDescription()->functionName, 'is_null');
        $this->AssertEquals($expr->getType() instanceof Boolean, true);
        $paramExpressions = $expr->getParamExpressions();
        $this->AssertEquals($paramExpressions[0] instanceof PropertyAccessExpression, true);

        $expression = 'Country ge \'India\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getNodeType(), ExpressionType::GREATERTHAN_OR_EQUAL);
        $this->AssertEquals($expr->getLeft() instanceof FunctionCallExpression, true);
        $this->AssertEquals($expr->getLeft()->getFunctionDescription()->functionName, 'strcmp');
        $paramExpression = $expr->getLeft()->getParamExpressions();
        $this->AssertEquals($paramExpression[0] instanceof PropertyAccessExpression, true);
        $this->AssertEquals($paramExpression[1] instanceof ConstantExpression, true);
        $this->AssertEquals($paramExpression[0]->getType() instanceof String, true);
        $this->AssertEquals($paramExpression[1]->getType() instanceof String, true);
        $this->AssertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->AssertEquals($expr->getRight()->getValue(), 0);

        $expression = "1F gt 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for gt operator\'s incompatible between Edm.Single and Edm.Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'gt\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "Rating lt null";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for lt operator\'s incompatible for null was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('The operator \'lt\' at position 7 is not supported for the \'null\' literal; only equality checks', $exception->getMessage());
        }


    }

    public function testLogicalExpression()
    {
        $expression = 'true or false';
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof LogicalExpression, true);
        $this->AssertEquals($expr->getType() instanceof Boolean, true);

        $expression = "1 add 2 gt 5 and 5 le 8";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof LogicalExpression, true);
        $this->AssertEquals($expr->getNodeType(), ExpressionType::AND_LOGICAL);
        $this->AssertEquals($expr->getLeft() instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getRight() instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getLeft()->getNodeType(), ExpressionType::GREATERTHAN);
        $this->AssertEquals($expr->getRight()->getNodeType(), ExpressionType::LESSTHAN_OR_EQUAL);
        $this->AssertEquals($expr->getLeft()->getLeft() instanceof ArithmeticExpression, true);
        $this->AssertEquals($expr->getLeft()->getRight() instanceof ConstantExpression, true);

        $expression = '1 add (2 gt 5) and 5 le 8';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for add operator\'s incompatible between Edm.Int32 and Edm.Boolean was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.Boolean', $exception->getMessage());
        }

        $expression = '12 or 34.5';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for or operator\'s incompatible between Edm.Int32 and Edm.Double was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'or\' incompatible with operand types Edm.Int32 and Edm.Double', $exception->getMessage());
        }

        $expression = '12.6F and true';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.Single and Edm.Boolean was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.Single and Edm.Boolean', $exception->getMessage());
        }

        $expression = '\'string1\' and \'string2\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.String and Edm.String was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.String and Edm.String', $exception->getMessage());
        }

    }

    public function testUnaryExpression()
    {
        $expression = "-Rating";
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof UnaryExpression, true);
        $this->AssertEquals($expr->getNodeType(), ExpressionType::NEGATE);
        $this->AssertEquals($expr->getChild() instanceof PropertyAccessExpression, true);

        $expression = "not(1 gt 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof UnaryExpression, true);
        $this->AssertEquals($expr->getNodeType(), ExpressionType::NOT_LOGICAL);
        $this->AssertEquals($expr->getType() instanceof Boolean, true);
        $this->AssertEquals($expr->getChild() instanceof RelationalExpression, true);

        $expression = '-\'myString\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for negate operator\'s incompatible with Edm.String was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'-\' incompatible with operand types Edm.String at position 0', $exception->getMessage());
        }


        $expression = 'not(1 mul 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for not operator\'s incompatible with Edm.Int32 was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'not\' incompatible with operand types Edm.Int32 at position 0', $exception->getMessage());
        }

    }

    public function testFunctionCallExpression()
    {
        $expression = 'year(datetime\'1988-11-11\')';
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof FunctionCallExpression, true);
        $this->AssertEquals($expr->getType() instanceof Int32, true);

        $expression = "substring('pspl', 1) eq 'pl'";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->AssertEquals($expr instanceof RelationalExpression, true);
        $this->AssertEquals($expr->getLeft() instanceof FunctionCallExpression, true);
        $this->AssertEquals($expr->getLeft()->getFunctionDescription()->functionName, 'strcmp');
        $paramExpressions = $expr->getLeft()->getParamExpressions();
        $this->AssertEquals(count($paramExpressions), 2);
        $this->AssertEquals($paramExpressions[0] instanceof FunctionCallExpression, true);
        $this->AssertEquals($paramExpressions[0]->getFunctionDescription()->functionName, 'substring');
        $paramExpressions1 = $paramExpressions[0]->getParamExpressions();
        $this->AssertEquals(count($paramExpressions1), 2);
        $this->AssertEquals($paramExpressions1[0] instanceof ConstantExpression, true);
        $this->AssertEquals($paramExpressions1[0]->getValue(), '\'pspl\'');

        $expression = 'unknownFun(1, 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and unknown function was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Unknown function \'unknownFun\' at position 0', $exception->getMessage());
        }

        $expression = 'endswith(\'mystring\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for function without closing bracket was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Close parenthesis expected', $exception->getMessage());
        }

        $expression = 'trim()';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for trim');

        } catch(ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'trim\' at position 0 with the specified arguments. The functions considered are: Edm.String trim(Edm.String)', $exception->getMessage());
        }


        $expression = 'month(123.4)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for month');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'month\' at position', $exception->getMessage());
        }

    }

    protected function tearDown()
    {    
    }
}

