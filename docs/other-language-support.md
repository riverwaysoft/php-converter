## How to add support for other languages?

To create a custom converter, you can implement the [OutputGeneratorInterface](/src/OutputGenerator/OutputGeneratorInterface.php). Here's an example illustrating how to do this for the Go language: [GoGeneratorSimple](/tests/GoOutputGeneratorSimple.php). You can understand how to use it by referring to [here](/tests/GoGeneratorSimpleTest.php). This covers only basic scenarios to give you an idea, so feel free to modify it according to your needs.
