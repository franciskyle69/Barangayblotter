import CentralLayout from "../Layouts/CentralLayout";
import SystemUpdaterPanel from "../../Components/SystemUpdaterPanel";

export default function SuperSettings() {
    return (
        <CentralLayout>
            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <p className="text-sm text-slate-500">Pages / Settings</p>
                    <h1 className="text-3xl font-bold text-slate-900">
                        Settings
                    </h1>
                    <p className="mt-1 text-sm text-slate-600">
                        System maintenance and update tools.
                    </p>
                </div>

                <SystemUpdaterPanel
                    title="System updater"
                    description="Download and install the latest GitHub Release (runs asynchronously)."
                />
            </div>
        </CentralLayout>
    );
}

