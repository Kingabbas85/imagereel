import { Video, CheckCircle2, Clock, TrendingUp } from 'lucide-react'

export default function Dashboard() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { icon: Video,        label: 'Total Videos',   value: '8',  iconBg: 'bg-violet-100', iconColor: 'text-violet-600' },
          { icon: CheckCircle2, label: 'Completed',       value: '4',  iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600' },
          { icon: Clock,        label: 'In Progress',     value: '2',  iconBg: 'bg-blue-100',   iconColor: 'text-blue-600' },
          { icon: TrendingUp,   label: 'This Month',      value: '6',  iconBg: 'bg-amber-100',  iconColor: 'text-amber-600' },
        ].map(({ icon: Icon, label, value, iconBg, iconColor }) => (
          <div key={label} className="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${iconBg} shrink-0`}>
              <Icon size={22} className={iconColor} />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-800 leading-none">{value}</p>
              <p className="text-sm text-slate-500 mt-1">{label}</p>
            </div>
          </div>
        ))}
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 className="font-semibold text-slate-800 mb-1">Welcome to ReelGen</h2>
        <p className="text-sm text-slate-500">Go to <strong>My Videos</strong> to see all your generated videos.</p>
      </div>
    </div>
  )
}
