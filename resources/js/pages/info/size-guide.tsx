import { InfoPage } from '@/components/shop/info-page';

const sizes = [
    { size: 'XS', uk: '6–8', chest: '81–86', waist: '63–68' },
    { size: 'S', uk: '8–10', chest: '86–91', waist: '68–73' },
    { size: 'M', uk: '10–12', chest: '91–97', waist: '73–79' },
    { size: 'L', uk: '12–14', chest: '97–104', waist: '79–86' },
    { size: 'XL', uk: '14–16', chest: '104–112', waist: '86–94' },
];

export default function SizeGuide() {
    return (
        <InfoPage
            title="Size guide"
            description="Find your fit. All measurements are body measurements in centimetres."
            sections={[
                {
                    heading: 'Clothing sizes',
                    body: (
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/60 text-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-semibold">Size</th>
                                        <th className="px-4 py-3 font-semibold">UK</th>
                                        <th className="px-4 py-3 font-semibold">Chest (cm)</th>
                                        <th className="px-4 py-3 font-semibold">Waist (cm)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {sizes.map((row) => (
                                        <tr key={row.size}>
                                            <td className="text-foreground px-4 py-3 font-medium">{row.size}</td>
                                            <td className="px-4 py-3">{row.uk}</td>
                                            <td className="px-4 py-3">{row.chest}</td>
                                            <td className="px-4 py-3">{row.waist}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ),
                },
                {
                    heading: 'How to measure',
                    body: (
                        <ul className="list-disc space-y-2 pl-5">
                            <li>
                                <strong>Chest:</strong> measure around the fullest part, keeping the tape horizontal.
                            </li>
                            <li>
                                <strong>Waist:</strong> measure around your natural waistline.
                            </li>
                        </ul>
                    ),
                },
                {
                    heading: 'Between sizes?',
                    body: <p>Our knitwear and outerwear are cut with a relaxed fit, so we recommend sizing down if you prefer a closer fit.</p>,
                },
            ]}
        />
    );
}
