/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';

import './editor.scss';

export default function Edit(props) {
	const blockProps = useBlockProps();
	const { attributes, setAttributes } = props;
	const { question, options, correctAnswer } = attributes;

	const addOption = () => {
		setAttributes({ options: [...options, ''] });
	};

	const updateOption = (index, value) => {
		const newOptions = [...options];
		newOptions[index] = value;
		setAttributes({ options: newOptions });
	};

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
							onChange={(value) => setAttributes({ question: value })}
						/>
						<TextControl
							label={__('Correct Answer', 'blank-plugin')}
							value={correctAnswer}
							onChange={(value) => setAttributes({ correctAnswer: value })}
						/>
						{options.map((option, index) => (
							<div key={index} style={{ marginBottom: '10px' }}>
								<TextControl
									label={__('Option', 'blank-plugin') + ` ${index + 1}`}
									value={option}
									onChange={(value) => updateOption(index, value)}
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
							<label key={index}>
								<input type="radio" name="quiz_answer" value={option} disabled />
								{option}
							</label>
						))}
					</form>
				</div>
			</div>
		</>
	);
}