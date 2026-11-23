import { InfoPage } from '@/components/shop/info-page';

export default function About() {
    return (
        <InfoPage
            title="Our story"
            description="Considered clothing, made to last."
            sections={[
                {
                    heading: 'Fewer, better things',
                    body: (
                        <p>
                            Loom &amp; Lane began with a simple idea: make the clothes we actually want to wear every day, and make them well enough
                            to last for years. Every piece is designed in our London studio and produced in small runs.
                        </p>
                    ),
                },
                {
                    heading: 'Materials first',
                    body: (
                        <p>
                            We work with natural and recycled fibres from certified mills: extra-fine merino, European linen, organic cotton and
                            British waxed cotton. They feel better, wear better and age beautifully.
                        </p>
                    ),
                },
                {
                    heading: 'Made responsibly',
                    body: (
                        <p>
                            We visit every factory we work with and only partner with makers who pay fair wages and provide safe working conditions.
                            Our packaging is plastic-free and fully recyclable.
                        </p>
                    ),
                },
            ]}
        />
    );
}
