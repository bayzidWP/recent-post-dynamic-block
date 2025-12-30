import { PanelBody, SelectControl, FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

const FilterSettingsPanel = ({
    taxonomy,
    taxonomyOptions,
    setAttributes,
    availableTerms,
    terms = [],
    updateTerms,
}) => {
    const safeTerms = Array.isArray(availableTerms) ? availableTerms : [];

    const termNames = useMemo(
        () => safeTerms.map((term) => term.name),
        [safeTerms]
    );

    const selectedTermNames = useMemo(
        () =>
            safeTerms
                .filter((term) => terms.includes(term.id))
                .map((term) => term.name),
        [safeTerms, terms]
    );

    return (
        <PanelBody title={__('Filter Settings', 'recent-posts-showcase')}>
            <SelectControl
                label={__('Select Taxonomy', 'recent-posts-showcase')}
                value={taxonomy}
                options={taxonomyOptions}
                onChange={(value) =>
                    setAttributes({ taxonomy: value, terms: [] })
                }
            />

            {taxonomy && (
                <FormTokenField
                    label={__('Select Terms', 'recent-posts-showcase')}
                    value={selectedTermNames}
                    suggestions={termNames}
                    onChange={updateTerms}
                    __experimentalExpandOnFocus
                />
            )}
        </PanelBody>
    );
};

export default FilterSettingsPanel;
