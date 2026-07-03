import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element'; // Add useEffect
import { useRsvpmakerRest } from './useRsvpmakerRest.js';

export default function TimeBlock({ clientId, onTimeCalculated }) {
    const rsvpmaker_rest = useRsvpmakerRest();
    let start_time = new Date(rsvpmaker_rest.date.replace(' ', 'T'));

    const { previousBlocks, currentBlock } = useSelect((select) => {
        const allBlocks = select('core/block-editor').getBlocks();
        const currentIndex = allBlocks.findIndex((block) => block.clientId === clientId);
        return {
            currentBlock: allBlocks[currentIndex],
            previousBlocks: allBlocks.slice(0, currentIndex),
        };
    }, [clientId]);
    if (currentBlock?.attributes?.setDateTime) {
        start_time = new Date(currentBlock.attributes.setDateTime.replace(' ', 'T'));
		console.log('TimeBlock: using current block datetime', start_time);
    } else {
        previousBlocks.forEach((block) => {
            if (block.attributes?.duration) {
                if (block.attributes.setDateTime) {
                    start_time = new Date(block.attributes.setDateTime.replace(' ', 'T'));
                }
                start_time.setMinutes(start_time.getMinutes() + parseInt(block.attributes.duration));
            }
        });
		console.log('TimeBlock: using previous blocks to calculate datetime', start_time);
    }

    const formatted_time = rsvpmaker_rest.hour12
        ? start_time.toLocaleTimeString([], { weekday: 'long', hour: 'numeric', minute: '2-digit', hour12: true })
        : start_time.toLocaleTimeString([], { weekday: 'long', hour: '2-digit', minute: '2-digit', hour12: false });

    // Pushes the calculated time string up to the parent component safely
    useEffect(() => {
        if (onTimeCalculated) {
            onTimeCalculated(formatted_time);
        }
    }, [formatted_time, onTimeCalculated]);

    return <div className="blocktime">{formatted_time}</div>;
}