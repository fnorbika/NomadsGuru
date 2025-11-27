import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('nomadsguru/deal-filter', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Filter Options', 'nomadsguru')}>
                        <ToggleControl
                            label={__('Show Search', 'nomadsguru')}
                            checked={attributes.showSearch}
                            onChange={(showSearch) => setAttributes({ showSearch })}
                        />
                        <ToggleControl
                            label={__('Show Destination Filter', 'nomadsguru')}
                            checked={attributes.showDestination}
                            onChange={(showDestination) => setAttributes({ showDestination })}
                        />
                        <ToggleControl
                            label={__('Show Price Range', 'nomadsguru')}
                            checked={attributes.showPrice}
                            onChange={(showPrice) => setAttributes({ showPrice })}
                        />
                        <ToggleControl
                            label={__('Show Score Filter', 'nomadsguru')}
                            checked={attributes.showScore}
                            onChange={(showScore) => setAttributes({ showScore })}
                        />
                        <ToggleControl
                            label={__('Show Date Filters', 'nomadsguru')}
                            checked={attributes.showDates}
                            onChange={(showDates) => setAttributes({ showDates })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <ServerSideRender
                        block="nomadsguru/deal-filter"
                        attributes={attributes}
                    />
                </div>
            </>
        );
    },
    save: () => {
        return null; // Server-side rendered
    },
});
