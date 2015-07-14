<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for cloze exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ClozeAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getQuestion()
    {
        $question = '';
        $index = 0;

        $answer_array = explode('[[]]', formatReady(implode('[[]]', $this->vipsExercise->answerArray)));

        foreach ($this->vipsExercise->question as $questionPart) {
            if (is_array($questionPart) && count($questionPart) > 1 && $this->vipsExercise->chooseItem) {
                $question .= '<select name="answer_'.$index.'">';

                foreach ($questionPart as $answer) {
                    $question .= '<option>'.htmlReady($answer['content']).'</question>';
                }

                $question .= '</select>';
                $index++;
            } else if (is_array($questionPart)) {
                $question .= '<input type="text" name="answer_'.$index.'">';
                $index++;
            } else {
                $question .= $answer_array[$index];
            }
        }

        return nl2br($question);
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        if ($solution === null) {
            return '';
        }

        $solutionString = '';
        $index = 0;

        foreach ($this->vipsExercise->question as $questionPart) {
            if (is_array($questionPart)) {
                $correct = false;
                $correctAnswer = '';
                $answerList = array();

                foreach ($questionPart as $answer) {
                    if ($answer['points'] != 1) {
                        continue;
                    }

                    $correctAnswer = $answer['content'];

                    if ($correctAnswer == $solution[$index]) {
                        $correct = true;
                    }

                    $answerList[] = $correctAnswer;
                }

                if ($correct) {
                    $solutionString .= sprintf(
                        '<span class="correct_answer">%s</span>',
                        $solution[$index]
                    );
                } else {
                    $solutionString .= sprintf(
                        '<span class="incorrect_answer">%s</span><span class="correction">[%s]</span>',
                        $solution[$index],
                        implode('|', $answerList)
                    );
                }

                $index++;
            } else {
                $solutionString .= formatReady($questionPart, FALSE);
            }
        }

        return $solutionString;
    }
}
