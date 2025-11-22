/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';

import './editor.scss';

/**
 * Edit component for the Quiz block.
 *
 * @param {Object} props - Props passed from the Block Editor.
 * @return {JSX.Element} - The Edit component for the Quiz block.
 *
 * @example
 * <Edit {...props} />
 */
export default function Edit(props) {
	const blockProps = useBlockProps();
	const { attributes, setAttributes } = props;
	const { question, options, correctAnswer } = attributes;

	/**
	 * Adds a new option to the quiz block.
	 *
	 * @since 1.0.0
	 */
	const addOption = () => {
		setAttributes({ options: [...options, ''] });
	};

	/**
	 * Updates the value of an option in the quiz block.
	 *
	 * @param {number} index - The index of the option to update.
	 * @param {string} value - The new value of the option.
	 *
	 * @since 1.0.0
	 */
	const updateOption = (index, value) => {
		const newOptions = [...options];
		newOptions[index] = value;
		setAttributes({ options: newOptions });
	};

	/**
	 * Removes an option from the quiz block.
	 *
	 * @param {number} index - The index of the option to remove.
	 *
	 * @since 1.0.0
	 */
	const removeOption = (index) => {
		const newOptions = options.filter((_, i) => i !== index);
		setAttributes({ options: newOptions });
	};

	return (
		<>
			<div {...blockProps}>
				<InspectorControls>
					<PanelBody title={__('Quiz Settings', 'blank-plugin')}>
						<TextControl
							label={__('Question', 'blank-plugin')}
							value={question}
							onChange={(value) =>
								setAttributes({ question: value })
							}
						/>
						<TextControl
							label={__('Correct Answer', 'blank-plugin')}
							value={correctAnswer}
							onChange={(value) =>
								setAttributes({ correctAnswer: value })
							}
						/>
						{options.map((option, index) => (
							<div key={index} style={{ marginBottom: '10px' }}>
								<TextControl
									label={
										__('Option', 'blank-plugin') +
										` ${index + 1}`
									}
									value={option}
									onChange={(value) =>
										updateOption(index, value)
									}
								/>
								<Button
									isDestructive
									onClick={() => removeOption(index)}
									disabled={options.length <= 1}
								>
									{__('Remove Option', 'blank-plugin')}
								</Button>
							</div>
						))}
						<Button isPrimary onClick={addOption}>
							{__('Add Option', 'blank-plugin')}
						</Button>
					</PanelBody>
				</InspectorControls>
				<div className="quiz-block">
					<h3>{question}</h3>
					<form>
						{options.map((option, index) => (
							<label key={index} htmlFor={`option-${index}`}>
								<input
									type="radio"
									name="quiz_answer"
									value={option}
									id={`option-${index}`}
									disabled
								/>
								{option}
							</label>
						))}
					</form>
				</div>
			</div>
		</>
	);
}
